<?php

use App\Events\UserRegistered;
use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

function mockSocialiteUser(
    string $id = '12345',
    string $email = 'doctor@example.com',
    string $name = 'Dr. Tareq'
): void {
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->allows([
        'getId' => $id,
        'getEmail' => $email,
        'getName' => $name,
    ]);

    Socialite::shouldReceive('driver->stateless->user')
        ->once()
        ->andReturn($socialUser);
}

describe('Google Redirect', function () {
    it('returns a valid google redirect url', function () {
        $mockUrl = 'https://accounts.google.com/o/oauth2/auth?client_id=123';

        Socialite::shouldReceive('driver->stateless->redirect->getTargetUrl')
            ->once()
            ->andReturn($mockUrl);

        $response = getJson(route('google.redirect'));

        $response->assertOk()
            ->assertJsonPath('data.url', $mockUrl);

        expect($response->json('success'))->toBeTrue();
    });
});

describe('Google Callback', function () {
    beforeEach(function () {
        Event::fake();
        config(['services.frontend.url' => 'https://frontend.test']);
    });

    it('creates a new user, doctor profile, and social account on first login', function () {

        mockSocialiteUser(
            id: 'google-unique-id',
            email: 'new-doctor@example.com',
            name: 'Menna Baligh'
        );

        $response = get(route('google.callback'));

        $location = $response->headers->get('Location');

        expect($location)->toContain('#token=');

        $user = User::whereContact('new-doctor@example.com')->first();

        expect($user)
            ->not->toBeNull()
            ->name->toBe('Menna Baligh')
            ->type->toBe('doctor')
            ->doctor->not->toBeNull();

        assertDatabaseHas('doctors', [
            'user_id' => $user->id,
        ]);

        assertDatabaseHas('user_social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-unique-id',
        ]);

        Event::assertDispatched(UserRegistered::class, function ($event) use ($user) {
            return $event->user->id === $user->id && !empty($event->otpCode);
        });

        expect(User::count())->toBe(1);
    });

    it('logs in user directly if social account already exists', function () {

        $user = User::factory()->create([
            'contact' => 'existing-social@example.com',
        ]);

        UserSocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'existing-social-id',
        ]);

        mockSocialiteUser(
            id: 'existing-social-id',
            email: 'existing-social@example.com'
        );

        $response = get(route('google.callback'));

        $location = $response->headers->get('Location');

        expect($location)->toContain('#token=');

        expect(User::count())->toBe(1);
        expect(UserSocialAccount::count())->toBe(1);
    });

    it('redirects to frontend with error message and logs error when socialite fails', function () {

        Log::shouldReceive('error')->once();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andThrow(new Exception('Invalid state'));

        $response = get(route('google.callback'));

        $response->assertRedirect(
            'https://frontend.test?message=auth_failed'
        );
    });

});
