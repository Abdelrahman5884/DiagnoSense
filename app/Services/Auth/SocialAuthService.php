<?php

namespace App\Services\Auth;

use App\Events\UserRegistered;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Socialite;

class SocialAuthService
{
    public function getRedirectUrl(string $provider): string
    {
        return Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    public function handleProviderCallback(string $provider): array
    {
        $socialUser = Socialite::driver($provider)
            ->stateless()
            ->user();

        return DB::transaction(function () use ($provider, $socialUser) {

            $account = UserSocialAccount::with('user')
                ->where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($account) {
                return $this->authResponse($account->user);
            }

            $user = User::where('contact', $socialUser->getEmail())->first();

            if (! $user) {
                $user = $this->createUser($socialUser);
            }

            $this->linkSocialAccount($user, $provider, $socialUser);

            return $this->authResponse($user);
        });
    }

    private function createUser(SocialiteUser $socialUser): User
    {
        $user = User::create([
            'name' => $socialUser->getName(),
            'contact' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(16)),
            'type' => 'doctor',
            'is_active' => true,
        ]);
        $user->doctor()->create();
        event(new UserRegistered($user));

        return $user;
    }

    private function linkSocialAccount(User $user, string $provider, SocialiteUser $socialUser): void
    {
        $user->socialAccounts()->updateOrCreate(
            ['provider' => $provider],
            ['provider_id' => $socialUser->getId()]
        );
    }

    private function authResponse(User $user): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ];
    }
}
