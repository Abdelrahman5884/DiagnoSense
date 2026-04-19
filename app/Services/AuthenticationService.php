<?php

namespace App\Services;

use App\Events\UserRegistered;
use App\Models\User;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Events\OtpRequested;
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
private function validateOtpSafely(string $contact, string $otp): bool
{
    try {
        $result = $this->otp->validate($contact, $otp);
        return isset($result->status) && $result->status;
    } catch (\Throwable $e) {
        return false; 
    }
}
public function verifyOtp(array $data, string $type): bool
{
    $user = $this->getUser($data['contact']);

    if (! $user || $user->type !== $type) {
        return false;
    }

   if (! $this->validateOtpSafely($data['contact'], $data['otp'])) {
    return false;
}

    $user->update([
        'email_verified_at' => now(),
    ]);

    return true;
}

private function isAuthorizedUser(User $user, string $type): bool
{
    return $user->type === $type;
}
public function resendOtp(User $user, string $type): bool
{
    if (! $this->isAuthorizedUser($user, $type)) {
        return false;
    }

    $otpCode = $this->generateOtp($user->contact);

     OtpRequested::dispatch($user, $otpCode);

    return true;
}
}
