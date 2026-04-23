<?php


use App\Mail\ResetPasswordMail;
use App\Notifications\ResetPasswordSMSNotification;
use Illuminate\Support\Facades\{Hash, Mail, Notification};
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->doctor = createUserWithType('doctor', 'doctor@diagno.com');
    $this->doctor->update(['password' => Hash::make('password')]);

    $this->patient = createUserWithType('patient', '01012345678');
    $this->patient->update(['password' => Hash::make('password')]);

    $this->users = [
        'doctor' => $this->doctor,
        'patient' => $this->patient,
    ];
});

dataset('user_types', ['doctor', 'patient']);

describe('forgot password', function () {
    it('allows user to request a password reset otp via correct channel', function (string $userType) {
        Mail::fake();
        Notification::fake();

        $user = $this->users[$userType];

        $response = postJson(route('password.forgot', $userType), [
            'contact' => $user->contact
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OTP has been sent to your registered contact.'
            ]);

        if (filter_var($user->contact, FILTER_VALIDATE_EMAIL)) {
            Mail::assertQueued(ResetPasswordMail::class);
            Notification::assertNothingSent();
        } else {
            Notification::assertSentTo($user, ResetPasswordSMSNotification::class);
            Mail::assertNothingSent();
        }
    })->with('user_types');

    it('fails if contact does not exist', function (string $userType) {
        $response = postJson(route('password.forgot', $userType), [
            'contact' => 'non-existent@test.com'
        ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    })->with('user_types');
});


describe('verify otp', function () {
    it('returns a reset token when otp is valid', function (string $userType) {
        $user = $this->users[$userType];

        $otpMock = Mockery::mock(\Ichtrojan\Otp\Otp::class);
        $otpMock->shouldReceive('validate')->andReturn((object)['status' => true]);
        app()->instance(\Ichtrojan\Otp\Otp::class, $otpMock);

        $response = postJson(route('password.verify', $userType), [
            'contact' => $user->contact,
            'otp' => '123456'
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['reset_token']]);
    })->with('user_types');
});


describe('reset password', function () {
    it('successfully resets password with valid token and ability', function (string $userType) {
        $user = $this->users[$userType];

        $token = $user->createToken('reset', ['reset-password'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('password.reset', $userType), [
                'password' => 'NewStrongPass123',
                'password_confirmation' => 'NewStrongPass123'
            ]);

        $response->assertOk();
        expect(Hash::check('NewStrongPass123', $user->refresh()->password))->toBeTrue();
        expect($user->tokens()->count())->toBe(0);
    })->with('user_types');

    it('prevents reset if token lacks reset-password ability', function (string $userType) {
        $user = $this->users[$userType];

        $token = $user->createToken('fake', ['view-profile'])->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('password.reset', $userType), [
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123'
            ]);

        $response->assertStatus(403);
    })->with('user_types');
});
