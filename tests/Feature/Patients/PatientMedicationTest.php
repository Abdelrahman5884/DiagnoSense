<?php

use App\Models\Doctor;
use App\Models\Medication;
use App\Models\User;
use App\Models\Visit;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->patient = $this->user->patient;

    $this->doctor = Doctor::factory()->create();

    $this->visit = Visit::factory()->create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
    ]);

    $this->medication1 = Medication::factory()->create([
        'visit_id' => $this->visit->id,
        'name' => 'Panadol',
        'dosage' => '500mg',
        'frequency' => 'Once daily',
        'duration' => '5 days',
    ]);

    $this->medication2 = Medication::factory()->create([
        'visit_id' => $this->visit->id,
        'name' => 'Catafast',
        'dosage' => '50mg',
        'frequency' => 'Twice daily',
        'duration' => null,
    ]);

    actingAs($this->user);
});

it('returns a successful response with the correct medications list and structure', function () {
    $response = getJson(route('medications.index'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Patient medications retrieved successfully')
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'dosage', 'frequency', 'duration'],
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Panadol',
            'dosage' => '500mg',
            'frequency' => 'Once daily',
            'duration' => '5 days',
        ])
        ->assertJsonFragment([
            'name' => 'Catafast',
            'dosage' => '50mg',
            'frequency' => 'Twice daily',
            'duration' => 'N/A',
        ]);
});

it('returns a 404 error if the user does not have a patient profile', function () {
    $doctorUser = User::factory()->create(['type' => 'doctor']);
    actingAs($doctorUser);

    $response = getJson(route('medications.index'));

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'No patient profile found for the user.');
});
