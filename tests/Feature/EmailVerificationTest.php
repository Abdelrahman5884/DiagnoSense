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

    $doctorWithEmail = createUserWithType('doctor', 'doctor@gmail.com');
    $patientWithEmail = createUserWithType('patient', 'patient@gmail.com');

    $this->users = [
        'doctor' => $doctorWithEmail,
        'patient' => $patientWithEmail,
    ];

    foreach ($this->users as $type => $user) {
        $this->validData[$type] = [
            'contact' => $user->contact,
            'otp' => '123456',
        ];
    }
});

dataset('user_types', ['doctor', 'patient']);

dataset('invalid_data', [
    'empty contact' => [['contact' => null]],
    'empty otp' => [['otp' => null]],
]);

it('allows user to verify email', function (string $userType) {
    $user = $this->users[$userType];
    $data = $this->validData[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, $data);

    $response->assertStatus(200);
})->with('user_types');

it('fails verification with invalid otp', function (string $userType) {
    Notification::fake();
    Mail::fake();

    $user = $this->users[$userType];
    $token = $user->createToken('test')->plainTextToken;

    $data = [
        'contact' => $user->contact,
        'otp' => '999999',
    ];

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, $data);

    $response->assertStatus(400);
})->with('user_types');

it('fails verification with invalid data', function (string $userType, array $invalidData) {
    $user = $this->users[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $data = array_merge($this->validData[$userType], $invalidData);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/verify-email/'.$userType, $data);

    $response->assertStatus(422);
})->with('user_types', 'invalid_data');

it('allows user to resend otp', function (string $userType) {
    $user = $this->users[$userType];

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/resend-otp/'.$userType)
        ->assertStatus(200);

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