<?php

namespace App\Services;



use App\Events\UserRegistered;
use App\Models\User;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;

class AuthenticationService
{
    public function __construct(
        protected Otp $otp
    ){}
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

    private function getToken(User $user): string
    {
        return $user->createToken('auth_token.'.$user->name)->plainTextToken;
    }

    private function generateOtp(string $contact): string
    {
        return $this->otp->generate($contact, 'numeric', 6, 10)->token;
    }
}
