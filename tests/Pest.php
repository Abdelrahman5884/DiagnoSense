<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createUserWithType(string $type, string $contact): User
{
    $user = User::factory()->create([
        'type' => $type,
        'contact' => $contact,
    ]);

    if ($type === 'doctor') {
        Doctor::factory()->create([
            'user_id' => $user->id,
        ]);
    } else {
        Patient::factory()->create([
            'user_id' => $user->id,
        ]);
    }

    return $user;
}

function createOtpInDatabase(string $contact, string $token, bool $expired = false): void
{
    DB::table('otps')->insert([
        'identifier' => $contact,
        'token' => $token,
        'valid' => true,
        'validity' => 10,
        'created_at' => $expired ? now()->subMinutes(11) : now(),
        'updated_at' => $expired ? now()->subMinutes(11) : now(),
    ]);
}
function getDataSets(string $userType, $test): array
{
    return array_values($test->validData[$userType]);
}

function validPatientData(): array
{
    return [
        'name' => fake()->name(),
        'contact' => fake()->unique()->safeEmail(),
        'date_of_birth' => fake()->date(),
        'gender' => fake()->randomElement(['male', 'female']),
        'national_id' => (string) fake()->numberBetween(1000000000, 9999999999),
        'is_smoker' => fake()->boolean(),
        'chronic_diseases' => ['diabetes', 'hypertension'],
        'previous_surgeries_name' => fake()->word(),
        'current_medications' => fake()->word(),
        'allergies' => fake()->word(),
        'family_history' => fake()->word(),
        'lab' => [UploadedFile::fake()->create('lab_results.pdf', 100, 'application/pdf')],
        'radiology' => [UploadedFile::fake()->create('radiology_report.pdf', 100, 'application/pdf')],
        'medical_history' => [UploadedFile::fake()->create('medical_history.pdf', 100, 'application/pdf')],
        'current_complaints' => fake()->word(),
    ];
}

function createDoctorWithBilling(string $billingMode = 'pay-per-use', int $balance = 100000): User
{
    $user = createUserWithType('doctor', fake()->unique()->safeEmail());
    $user->doctor->billing_mode = $billingMode;
    $user->doctor->save();
    $user->doctor->wallet()->create(['balance' => $balance]);

    return $user;
}

function fakeAiResponse(): array
{
    return [
        'key_information' => [
            'ai_insight' => 'Test AI Insight',
            'ai_summary' => 'Test AI Summary',
            'high_priority_alerts' => [
                [
                    'title' => 'High Priority Alert 1',
                    'insight' => 'Insight 1',
                    'evidence' => [
                        'Evidence 1',
                        'Evidence 2',
                    ],
                ],
            ],
            'low_priority_alerts' => [
                [
                    'title' => 'Low Priority Alert 1',
                    'insight' => 'Insight 1',
                    'evidence' => [
                        'Evidence 1',
                        'Evidence 2',
                    ],
                ],
            ],
            'medium_priority_alerts' => [
                [
                    'title' => 'Medium Priority Alert 1',
                    'insight' => 'Insight 1',
                    'evidence' => [
                        'Evidence 1',
                        'Evidence 2',
                    ],
                ],
            ],
        ],
        'decision_support' => [
            [
                'condition' => 'Condition 1',
                'probability' => 0.8,
                'status' => 'Positive',
                'clinical_reasoning' => 'Clinical Reasons 1',
            ],
            [
                'condition' => 'Condition 2',
                'probability' => 0.6,
                'status' => 'Negative',
                'clinical_reasoning' => 'Clinical Reasons 2',
            ],
        ],
        'message' => 'Analysis completed successfully',
        'pdf_path' => 'path/to/ocr/report.pdf',
    ];
}
