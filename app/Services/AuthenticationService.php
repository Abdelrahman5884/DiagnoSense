<?php

namespace App\Services;

use App\Events\UserRegistered;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use App\Notifications\EmailVerificationSMSNotification;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthenticationService
{
    public function __construct(
        protected Otp $otp
    ) {}

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->doctor()->create([
                'specialization' => $data['specialization'],
            ]);

            $token = $this->getToken($user);
            $userId = $user->doctor->id;
            $otpCode = $this->generateOtp($user->contact);

            UserRegistered::dispatch($user, $otpCode);

            return compact('user', 'token', 'userId');
        });
    }

    public function login(array $data, string $type): ?array
    {
        $user = $this->authenticate($data['contact'], $data['password']);
        if (! $user || $user->type !== $type) {
            return null;
        }

        $token = $this->getToken($user);
        $userId = $type == 'doctor' ? $user->doctor->id : $user->patient->id;

        return compact('user', 'token', 'userId');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function getToken(User $user): string
    {
        return $user->createToken('auth_token.'.$user->name)->plainTextToken;
    }

    private function generateOtp(string $contact): string
    {
        return $this->otp->generate($contact, 'numeric', 6, 10)->token;
    }

    private function getUser(string $contact): ?User
    {
        return User::where('contact', $contact)->first();
    }

    private function authenticate(string $contact, string $password): ?User
    {
        $user = $this->getUser($contact);
        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    private function sendOtp(User $user, string $otp): void
    {
        if (filter_var($user->contact, FILTER_VALIDATE_EMAIL)) {

            Mail::to($user->contact)->send(
                new EmailVerificationMail($user, $otp)
            );
        } else {
            $user->notify(new EmailVerificationSMSNotification($otp));
        }
    }

    private function validateOtp(string $contact, string $otp): bool
    {
        return $this->otp->validate($contact, $otp)->status;
    }

    public function verifyEmail(array $data): bool
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();

            if (! $this->validateOtp($user->contact, $data['otp'])) {
                return false;
            }

            $user->update([
                'contact_verified_at' => now(),
            ]);

            return true;
        });
    }

    public function resendOtp(User $user): bool
    {
        if ($user->contact_verified_at) {
            return false;
        }

        $otpCode = $this->generateOtp($user->contact);

        $this->sendOtp($user, $otpCode);

        return true;
    }

    public function verifyOtp(array $data, string $type): string|false
    {
        $user = $this->getUser($data['contact']);

        if (! $user || $user->type !== $type) {
            return false;
        }

        $result = $this->otp->validate($user->contact, $data['otp']);

        if (! $result->status) {
            return false;
        }

          $token = $user->createToken('password_reset_'.$user->id,['reset-password'],
          now()->addMinutes(15))->plainTextToken;

        return $token;
    }
}
