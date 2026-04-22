<?php

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const SUPPORT_ENDPOINT = '/api/v1/support';

beforeEach(function () {
    Storage::fake('public');

    $this->doctor = createUserWithType('doctor', 'doctor@test.com');
    $this->patient = createUserWithType('patient', 'patient@test.com');
});

describe('Support - Create Ticket', function () {

    it('allows doctor to create support ticket', function () {

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson(SUPPORT_ENDPOINT, [
                'category' => 'technical',
                'urgency' => 'high',
                'message' => 'Test message',
            ]);

        $response->assertStatus(201);

        expect(SupportTicket::count())->toBe(1);
    });

});

describe('Support - Attachment Upload', function () {

    it('allows doctor to upload attachment', function () {

        Storage::fake('azure');

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->post(SUPPORT_ENDPOINT, [
                'category' => 'technical',
                'urgency' => 'medium',
                'message' => 'Test with file',
                'attachment' => $file,
            ]);

        $response->assertStatus(201);

        $ticket = SupportTicket::first();

        expect($ticket->attachment_path)->not->toBeNull();

        Storage::disk('azure')->assertExists($ticket->attachment_path);
    });

});

describe('Support - Validation', function () {

    it('fails with invalid data', function () {

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson(SUPPORT_ENDPOINT, [
                'category' => 'wrong',
            ]);

        $response->assertStatus(422);
    });

});

describe('Support - Authorization', function () {

    it('fails if user is not doctor', function () {

        $response = $this->actingAs($this->patient, 'sanctum')
            ->postJson(SUPPORT_ENDPOINT, [
                'category' => 'technical',
                'urgency' => 'low',
                'message' => 'Test',
            ]);

        $response->assertStatus(403);
    });

});

describe('Support - Guest', function () {

    it('fails if not authenticated', function () {

        $this->postJson(SUPPORT_ENDPOINT, [
            'category' => 'technical',
            'urgency' => 'low',
            'message' => 'Test',
        ])->assertStatus(401);
    });

});