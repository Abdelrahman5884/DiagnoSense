<?php

use App\Models\User;
use App\Models\Visit;
use App\Models\Task;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->patient = $this->user->patient;

    $this->doctorUser = createUserWithType('doctor', fake()->unique()->safeEmail(), 'Menna Baligh');
    $this->doctor = $this->doctorUser->doctor;

    $this->visit = Visit::factory()->create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'next_visit_date' => '2026-08-20 06:41:00',
        'created_at' => now()->subDays(2),
    ]);

    $this->task = Task::factory()->create([
        'visit_id' => $this->visit->id,
        'title' => 'title task 11',
        'description' => '',
        'created_at' => now(),
    ]);

    actingAs($this->user);
});

it('returns a successfully combined and sorted timeline of visits and tasks', function () {
    $response = getJson(route('timeline.index'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Timeline retrieved successfully')
        ->assertJsonStructure([
            'data' => [
                '*' => ['type', 'title', 'description', 'doctor', 'day', 'month', 'year']
            ]
        ]);

    $data = $response->json('data');

    expect($data)->toHaveCount(2);
    expect($data[0]['type'])->toBe('TASK');
    expect($data[0]['title'])->toBe('title task 11');
    expect($data[0]['doctor'])->toBe('Dr. Menna Baligh');

    expect($data[1]['type'])->toBe('VISIT');
    expect($data[1]['title'])->toBe('Visit');
    expect($data[1]['description'])->toBe('2026-08-20 06:41 AM');
    expect($data[1]['doctor'])->toBe('Dr. Menna Baligh');
});

it('returns a 404 error if the user has no patient profile', function () {
    $doctorUser = User::factory()->create(['type' => 'doctor']);
    actingAs($doctorUser);

    $response = getJson(route('timeline.index'));

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'No patient profile found for the user.');
});
