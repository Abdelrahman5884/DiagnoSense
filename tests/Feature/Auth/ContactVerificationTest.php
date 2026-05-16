<?php

use App\Mail\EmailVerificationMail;
use App\Notifications\EmailVerificationSMSNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/*
|--------------------------------------------------------------------------
| Setup
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $doctorWithEmail = createUserWithType('doctor', 'doctor@email.com');
    $doctorWithPhone = createUserWithType('doctor', '01012345678');

    $doctorWithEmail->update(['contact_verified_at' => null]);
    $doctorWithPhone->update(['contact_verified_at' => null]);

    $this->users = [
        'email_user' => $doctorWithEmail,
        'phone_user' => $doctorWithPhone,
    ];
});

dataset('contact_methods', ['email_user', 'phone_user']);
dataset('invalid_data', ['empty otp' => [['otp' => null]]]);

/*
|--------------------------------------------------------------------------
| Verify Contact Section
|--------------------------------------------------------------------------
*/

describe('Verify Contact', function () {

    it('allows user to verify contact successfully with valid otp', function ($method) {
        $user = $this->users[$method];
        $token = '123456';

        createOtpInDatabase($user->contact, $token, expired: false);

        $this->actingAs($user, 'sanctum')
            ->postJson(route('verify-contact'), ['otp' => $token])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User verified successfully.',
            ]);

        expect($user->fresh()->contact_verified_at)->not->toBeNull();
    })->with('contact_methods');

    it('fails verification with expired otp', function ($method) {
        $user = $this->users[$method];
        $token = '123456';

        createOtpInDatabase($user->contact, $token, expired: true);

        $this->actingAs($user, 'sanctum')
            ->postJson(route('verify-contact'), ['otp' => $token])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ]);
    })->with('contact_methods');

    it('fails verification with wrong otp (not in database)', function ($method) {
        $user = $this->users[$method];

        $this->actingAs($user, 'sanctum')
            ->postJson(route('verify-contact'), ['otp' => '999999'])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ]);
    })->with('contact_methods');

    it('fails verification if data is invalid', function ($method, $data) {
        $user = $this->users[$method];

        $this->actingAs($user, 'sanctum')
            ->postJson(route('verify-contact'), $data)
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Errors',
            ]);
    })->with('contact_methods', 'invalid_data');

    it('denies access to verify contact without authentication', function () {
        $this->postJson(route('verify-contact'), ['otp' => '123456'])
            ->assertStatus(401);
    });

});

/*
|--------------------------------------------------------------------------
| Resend OTP Section
|--------------------------------------------------------------------------
*/

describe('Resend OTP', function () {

    it('allows user to resend a new otp via proper channel', function ($method) {
        $user = $this->users[$method];

        $this->actingAs($user, 'sanctum')
            ->getJson(route('resend-otp'))
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully.',
            ]);

        if (filter_var($user->contact, FILTER_VALIDATE_EMAIL)) {
            Mail::assertSent(EmailVerificationMail::class);
        } else {
            Notification::assertSentTo($user, EmailVerificationSMSNotification::class);
        }
    })->with('contact_methods');

    it('fails to resend otp if user is already verified', function ($method) {
        $user = $this->users[$method];
        $user->update(['contact_verified_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->getJson(route('resend-otp'))
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'User already verified.',
            ]);
    })->with('contact_methods');

    it('denies access to resend otp without authentication', function () {
        $this->getJson(route('resend-otp'))
            ->assertStatus(401);
    });

});
