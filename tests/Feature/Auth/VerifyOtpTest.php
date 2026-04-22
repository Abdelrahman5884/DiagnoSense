<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const VERIFY_OTP_ENDPOINT = '/api/v1/auth/verify-otp';

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
    'otp more than 6 digits' => [
        ['otp' => '1234567'],
        ['otp' => ['The otp field must be 6 characters.']],
    ],
    'invalid contact format' => [
        ['contact' => 'not-valid'],
        ['contact' => ['The contact must be a valid email address or a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.']],
    ],
]);

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function createOtp($contact, $otp = '123456')
{
    DB::table('otps')->updateOrInsert(
        ['identifier' => $contact],
        [
            'token' => $otp,
            'validity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
}

/*
|--------------------------------------------------------------------------
| VERIFY OTP
|--------------------------------------------------------------------------
*/

describe('Verify OTP', function () {

    it('allows user to verify otp and returns reset token', function (string $userType) {

        $contacts = [
            $this->contacts[$userType]['email'],
            $this->contacts[$userType]['phone'],
        ];

        foreach ($contacts as $contact) {

            createOtp($contact);

            $response = $this->postJson(
                VERIFY_OTP_ENDPOINT . '/' . $userType,
                [
                    'contact' => $contact,
                    'otp' => '123456',
                ]
            );

            $response->assertStatus(200);

            $response->assertJsonStructure([
                'success',
                'message',
                'data' => ['reset_token'],
            ]);

            $response->assertJson([
                'success' => true,
                'message' => 'OTP verified. Use this token to reset your password.',
            ]);

            expect($response->json('data.reset_token'))->not->toBeEmpty();
        }

    })->with('user_types');


    it('fails when otp is invalid or expired', function (string $userType) {

        $contacts = [
            $this->contacts[$userType]['email'],
            $this->contacts[$userType]['phone'],
        ];

        foreach ($contacts as $contact) {

            $response = $this->postJson(
                VERIFY_OTP_ENDPOINT . '/' . $userType,
                [
                    'contact' => $contact,
                    'otp' => '000000',
                ]
            );

            $response->assertStatus(400);

            $response->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ]);
        }

    })->with('user_types');


    it('fails verify otp with invalid data', function (
        string $userType,
        array $invalidData,
        array $expectedErrors
    ) {

        $contacts = [
            $this->contacts[$userType]['email'],
            $this->contacts[$userType]['phone'],
        ];

        foreach ($contacts as $contact) {

            $validData = [
                'contact' => $contact,
                'otp' => '123456',
            ];

            $response = $this->postJson(
                VERIFY_OTP_ENDPOINT . '/' . $userType,
                array_merge($validData, $invalidData)
            );

            $response->assertStatus(422);

            $response->assertJson([
                'success' => false,
                'message' => 'Validation Errors',
                'data' => $expectedErrors,
            ]);
        }

    })->with('user_types', 'invalid_otp_data');

});