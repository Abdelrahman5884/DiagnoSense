<?php

use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Ichtrojan\Otp\Otp;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Mail::fake();

    $mock = \Mockery::mock(Otp::class);

    $mock->shouldReceive('validate')
        ->andReturnUsing(function ($contact, $otp) {
            return (object)[
                'status' => $otp === '123456'
            ];
        });

    $mock->shouldReceive('generate')
        ->andReturn((object)[
            'token' => '123456'
        ]);

    $this->app->instance(Otp::class, $mock);

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

it('allows user to verify email', function (string $userType) {
    $user = $this->users[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, [
            'otp' => '123456'
        ]);

    $response->assertStatus(200);
})->with('user_types');

it('fails verification with invalid otp', function (string $userType) {
    $user = $this->users[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, [
            'otp' => '999999'
        ]);

    $response->assertStatus(401);
})->with('user_types');

it('fails verification with invalid data', function (string $userType, array $invalidData) {
    $user = $this->users[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, $invalidData);

    $response->assertStatus(422);
})->with('user_types', 'invalid_data');

it('allows user to resend otp', function (string $userType) {
    $user = $this->users[$userType];

    $user->update(['contact_verified_at' => null]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/resend-otp/'.$userType);

    $response->assertStatus(200);

    Notification::assertSentTo(
        $user,
        EmailVerificationNotification::class
    );
})->with('user_types');

it('fails resend otp without auth', function (string $userType) {
    $this->getJson('/api/v1/resend-otp/'.$userType)
        ->assertStatus(401);
})->with('user_types');

it('fails resend otp with wrong user type', function () {
    $user = $this->users['doctor'];

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/resend-otp/patient')
        ->assertStatus(403);
});

it('fails resend otp if already verified', function (string $userType) {
    $user = $this->users[$userType];

    $user->update([
        'contact_verified_at' => now()
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/resend-otp/'.$userType)
        ->assertStatus(400);
})->with('user_types');