<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\SupportTicket;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->doctor = createUserWithType('doctor', 'doctor@test.com');
    $this->patient = createUserWithType('patient', 'patient@test.com');
});

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

it('allows doctor to create support ticket', function () {

    $response = $this->actingAs($this->doctor, 'sanctum')
        ->postJson('/api/v1/support', [
            'category' => 'technical',
            'urgency' => 'high',
            'message' => 'Test message',
        ]);

    $response->assertStatus(201);

    $response->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('support_tickets', [
        'doctor_id' => $this->doctor->doctor->id,
        'category' => 'technical',
        'urgency' => 'high',
    ]);
});

/*
|--------------------------------------------------------------------------
| WITH ATTACHMENT
|--------------------------------------------------------------------------
*/
it('allows doctor to upload attachment', function () {

    Storage::fake('azure');

    $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($this->doctor, 'sanctum')
        ->post('/api/v1/support', [
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
/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

it('fails with invalid data', function () {

    $response = $this->actingAs($this->doctor, 'sanctum')
        ->postJson('/api/v1/support', [
            'category' => 'wrong',
        ]);

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| UNAUTHORIZED
|--------------------------------------------------------------------------
*/

it('fails if user is not doctor', function () {

    $response = $this->actingAs($this->patient, 'sanctum')
        ->postJson('/api/v1/support', [
            'category' => 'technical',
            'urgency' => 'low',
            'message' => 'Test',
        ]);

    $response->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| GUEST
|--------------------------------------------------------------------------
*/

it('fails if not authenticated', function () {

    $this->postJson('/api/v1/support', [
        'category' => 'technical',
        'urgency' => 'low',
        'message' => 'Test',
    ])->assertStatus(401);
});