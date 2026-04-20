<?php

namespace App\Services;

use App\Events\UserRegistered;
use App\Models\User;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\EmailVerificationSMSNotification;

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

    private function isAuthorizedUser(User $user, string $type): bool
    {
        return $user->type === $type;
    }
    private function sendOtp(User $user, string $otp): void
    {
        try {
             if (filter_var($user->contact, FILTER_VALIDATE_EMAIL)) {
               $user->notify(new EmailVerificationNotification($otp));
            } else {
               $user->notify(new EmailVerificationSMSNotification($otp));
            }
        } catch (\Throwable $e) {
             logger()->error('OTP sending failed', [
             'user_id' => $user->id,
             'error' => $e->getMessage()
          ]);
        }
    }

    public function verifyEmail(array $data, string $type): bool
    {
        return DB::transaction(function () use ($data, $type) {
        $user = auth()->user();
        if (! $this->isAuthorizedUser($user, $type)) {
                return false;
            }

        if ($user->contact_verified_at) {
            return false; 
        }

        $result = $this->otp->validate($user->contact, $data['otp']);
        if (! $result->status) {
            return false;
        }

        $user->update([
            'contact_verified_at' => now(),
        ]);
        return true;
       });
   }

    public function resendOtp(User $user, string $type): string|bool
    {
        if (! $this->isAuthorizedUser($user, $type)) {
            return false;
        }
        if ($user->contact_verified_at) {
            return 'already_verified';
       }  

       $otpCode = $this->generateOtp($user->contact);

       $this->sendOtp($user, $otpCode);

      return true;
   }
    public function verifyOtp(array $data): string|false
    {
        
        $result = $this->otp->validate($data['contact'], $data['otp']);

        if (! $result->status) {
           return false;
        }

        $token = \Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['contact']],
             [
               'token'      => hash('sha256', $token),
               'created_at' => now(),
             ]
        );

        return $token;
    }
}
