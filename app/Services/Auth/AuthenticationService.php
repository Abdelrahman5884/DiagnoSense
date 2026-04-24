<?php

namespace App\Services\Auth;

use App\Events\UserRegistered;
use App\Helpers\Auth;
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
            $user->doctor()->create();

            $token = Auth::getToken($user);
            $userId = $user->doctor->id;
            $otpCode = Auth::generateOtp($user->contact, $this->otp);

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

        $token = Auth::getToken($user);
        $userId = $type == 'doctor' ? $user->doctor->id : $user->patient->id;

        return compact('user', 'token', 'userId');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
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

    public function verifyContact(array $data): bool
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

        $otpCode = Auth::generateOtp($user->contact, $this->otp);

        $this->sendOtp($user, $otpCode);

        return true;
    }

    public function verifyOtp(array $data, string $type): string|false
    {
        $user = $this->getUser($data['contact']);

        if (! $user || $user->type !== $type) {
            throw new \Exception('', 403);
        }

        $result = $this->otp->validate($user->contact, $data['otp']);

        if (! $result->status) {
            throw new \Exception('', 401);
        }

        $token = $user->createToken('password_reset_'.$user->id, ['reset-password'],
            now()->addMinutes(15))->plainTextToken;

        return $token;
    }
}
