<?php

use App\Mail\EmailVerificationMail;
use Ichtrojan\Otp\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->otpMock = \Mockery::mock(Otp::class);
    $this->otpMock->shouldReceive('generate')
        ->andReturn((object) [
            'token' => '123456',
        ]);
    $this->app->instance(Otp::class, $this->otpMock);

    $doctor = createUserWithType('doctor', 'doctor@gmail.com');
    $patient = createUserWithType('patient', 'patient@gmail.com');

    $doctor->update(['contact_verified_at' => null]);
    $patient->update(['contact_verified_at' => null]);

    $this->users = [
        'doctor' => $doctor,
        'patient' => $patient,
    ];
});

dataset('user_types', ['doctor', 'patient']);

dataset('invalid_data', [
    'empty otp' => [['otp' => null]],
]);

/*
|--------------------------------------------------------------------------
| VERIFY EMAIL
|--------------------------------------------------------------------------
*/

it('allows user to verify email', function ($type) {
    $this->otpMock->shouldReceive('validate')
        ->withAnyArgs()
        ->andReturn((object) ['status' => true]);

    $user = $this->users[$type];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/verify-email', ['otp' => '123456'])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'User verified successfully.',
        ]);

    $this->assertNotNull($user->fresh()->contact_verified_at);
})->with('user_types');

it('fails verification with invalid otp', function ($type) {
    $this->otpMock->shouldReceive('validate')
        ->withAnyArgs()
        ->andReturn((object) ['status' => false]);

    $user = $this->users[$type];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/verify-email', ['otp' => '000000'])
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid or expired OTP.',
        ]);
})->with('user_types');

it('fails verification with invalid data', function ($type, $data) {
    $user = $this->users[$type];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/verify-email', $data)
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation Errors',
        ]);
})->with('user_types', 'invalid_data');

it('fails verification without auth', function ($type) {
    $this->postJson('/api/v1/auth/verify-email', ['otp' => '123456'])
        ->assertStatus(401);
})->with('user_types');

/*
|--------------------------------------------------------------------------
| RESEND OTP
|--------------------------------------------------------------------------
*/

it('allows user to resend otp', function ($type) {
    $user = $this->users[$type];

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/resend-otp')
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);

    Mail::assertSent(EmailVerificationMail::class);
})->with('user_types');

it('fails resend otp without auth', function ($type) {
    $this->getJson('/api/v1/auth/resend-otp')
        ->assertStatus(401);
})->with('user_types');

it('fails resend otp if already verified', function ($type) {
    $user = $this->users[$type];
    $user->update(['contact_verified_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/resend-otp')
        ->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'User already verified.',
        ]);
})->with('user_types');
