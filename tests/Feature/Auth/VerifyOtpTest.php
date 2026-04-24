<?php


beforeEach(function () {
    $doctorWithEmail = createUserWithType('doctor', 'testDoctor@gmail.com');
    $patientWithEmail = createUserWithType('patient', 'testPatient@gmail.com');
    $doctorWithPhone = createUserWithType('doctor', '01004732940');
    $patientWithPhone = createUserWithType('patient', '01093159370');

    $this->contacts = [
        'doctor' => [
            'email' => $doctorWithEmail->contact,
            'phone' => $doctorWithPhone->contact,
        ],
        'patient' => [
            'email' => $patientWithEmail->contact,
            'phone' => $patientWithPhone->contact,
        ],
    ];
});

dataset('user_types', ['doctor', 'patient']);

dataset('invalid_otp_data', [
    'empty contact' => [
        ['contact' => null],
        ['contact' => ['The contact field is required.']],
    ],
    'empty otp' => [
        ['otp' => null],
        ['otp' => ['The otp field is required.']],
    ],
    'otp less than 6 digits' => [
        ['otp' => '12345'],
        ['otp' => ['The otp field must be 6 characters.']],
    ],
    'invalid contact format' => [
        ['contact' => 'not-valid'],
        ['contact' => ['The contact must be a valid email address or a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.']],
    ],
]);

/*
|--------------------------------------------------------------------------
| VERIFY OTP TESTS
|--------------------------------------------------------------------------
*/

describe('Verify OTP (Password Reset)', function () {

    it('allows user to verify otp successfully and returns reset token', function (string $userType) {
        $contacts = [$this->contacts[$userType]['email'], $this->contacts[$userType]['phone']];

        foreach ($contacts as $contact) {
            $token = '123456';
            createOtpInDatabase($contact, $token, expired: false);

            $response = $this->postJson(route('verify-otp', ['type' => $userType]), [
                'contact' => $contact,
                'otp'     => $token,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'OTP verified. You can now reset your password.',
                ]);

            expect($response->json('data.reset_token'))->not->toBeEmpty();
        }
    })->with('user_types');

    it('fails when otp is expired', function (string $userType) {
        $contacts = [$this->contacts[$userType]['email'], $this->contacts[$userType]['phone']];

        foreach ($contacts as $contact) {
            $token = '123456';
            createOtpInDatabase($contact, $token, expired: true);

            $response = $this->postJson(route('verify-otp', ['type' => $userType]), [
                'contact' => $contact,
                'otp'     => $token,
            ]);
            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ]);
        }
    })->with('user_types');

    it('fails when otp is wrong (not in database)', function (string $userType) {
        $contacts = [$this->contacts[$userType]['email'], $this->contacts[$userType]['phone']];

        foreach ($contacts as $contact) {
            $response = $this->postJson(route('verify-otp', ['type' => $userType]), [
                'contact' => $contact,
                'otp'     => '999999',
            ]);
            $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ]);
        }
    })->with('user_types');

    it('fails with validation errors', function (string $userType, array $invalidData, array $expectedErrors) {
        $contacts = [$this->contacts[$userType]['email'], $this->contacts[$userType]['phone']];

        foreach ($contacts as $contact) {
            $response = $this->postJson(route('verify-otp', ['type' => $userType]), $invalidData);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation Errors',
                    'data'    => $expectedErrors,
                ]);
        }
    })->with('user_types', 'invalid_otp_data');

});