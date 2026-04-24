<?php

use App\Models\SupportTicket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Setup & Datasets
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Storage::fake('azure');

    $this->doctor = createUserWithType('doctor', 'doctor@test.com');
    $this->patient = createUserWithType('patient', 'patient@test.com');
});

dataset('invalid_support_data', [
    'empty category' => [
        ['category' => null],
        ['category' => ['The category field is required.']],
    ],
    'invalid category' => [
        ['category' => 'not-valid'],
        ['category' => ['The selected category is invalid.']],
    ],
    'empty urgency' => [
        ['urgency' => null],
        ['urgency' => ['The urgency field is required.']],
    ],
    'invalid urgency' => [
        ['urgency' => 'critical'], 
        ['urgency' => ['The selected urgency is invalid.']],
    ],
    'empty message' => [
        ['message' => null],
        ['message' => ['The message field is required.']],
    ],
    'invalid attachment (not file)' => [
        ['attachment' => 'not-a-file'],
        ['attachment' => ['The attachment field must be a file.']],
    ],
    'large attachment' => [
        fn() => [
            'category' => 'technical', 
            'urgency'  => 'high',
            'message'  => 'Valid message description',
            'attachment' => UploadedFile::fake()->create('heavy.pdf', 5001) 
        ],
        ['attachment' => ['The attachment field must not be greater than 5000 kilobytes.']],
    ],
]);

/*
|--------------------------------------------------------------------------
| SUPPORT TESTS
|--------------------------------------------------------------------------
*/

describe('Support - Create Ticket', function () {

    it('allows doctor to create support ticket successfully', function () {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson(route('support.create'), [
                'category' => 'technical',
                'urgency' => 'high',
                'message' => 'Test message for support',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Support message submitted successfully we will get back to you shortly.',
            ]);

        expect(SupportTicket::count())->toBe(1);
    });

    it('allows doctor to upload attachment and stores it correctly', function () {
        $file = UploadedFile::fake()->create('report.pdf', 500);

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson(route('support.create'), [
                'category' => 'billing',
                'urgency' => 'medium',
                'message' => 'Testing file upload',
                'attachment' => $file,
            ]);

        $response->assertStatus(201);

        $ticket = SupportTicket::first();
        expect($ticket->attachment_path)->not->toBeNull();
        
        Storage::disk('azure')->assertExists($ticket->attachment_path);
    });

});

describe('Support - Validation', function () {

    it('fails with invalid data', function (array|Closure $invalidData, array $expectedErrors) {
        $data = is_callable($invalidData) ? $invalidData() : $invalidData;

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson(route('support.create'), $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Errors',
                'data'    => $expectedErrors,
            ]);
    })->with('invalid_support_data');

});

describe('Support - Permissions & Security', function () {

    it('fails if user is not authenticated (guest)', function () {
        $this->postJson(route('support.create'), [
            'category' => 'technical',
            'urgency' => 'low',
            'message' => 'Guest message',
        ])->assertStatus(401);
    });

});
