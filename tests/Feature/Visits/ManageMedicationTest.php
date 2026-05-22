<?php

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createUserWithType('doctor', fake()->unique()->safeEmail());
    $this->patient = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->user->doctor->patients()->attach($this->patient->patient->id);
    actingAs($this->user);
    $this->visit = createVisit($this->user->doctor, $this->patient->patient);
    $this->medication = [
        'name' => 'Paracetamol',
        'dosage' => '100mg',
        'frequency' => 'daily',
        'duration' => '10 days',
        'action' => 'save',
    ];
});

it('allows doctor to add medication to visit successfully', function () {
    $response = $this->post(route('visits.medications.store', ['visit' => $this->visit->id]), $this->medication);
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'name',
            'dosage',
            'frequency',
            'duration',
            'action',
            'visit' => [
                'id',
                'next_visit_date',
                'status',
                'doctor_name',
                'specialization',
                'date',
                'time',
            ],
            'created_at',
            'updated_at',
        ],
    ]);
    $this->assertDatabaseHas('medications', [
        'name' => $this->medication['name'],
        'visit_id' => $this->visit->id,
    ]);
    $this->assertDatabaseHas('visits', [
        'id' => $this->visit->id,
        'status' => 'completed',
    ]);
});

it('prevents doctor from adding medication to unassigned patient', function () {
    $otherDoctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $response = actingAs($otherDoctor)->post(route('visits.medications.store', ['visit' => $this->visit->id]), $this->medication);
    $response->assertStatus(403);
});

it('allows doctor to delete medication successfully', function () {
    $medication = $this->visit->medications()->create($this->medication);
    $response = $this->delete(route('medications.destroy', ['medication' => $medication->id]));
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Medication deleted successfully',
        'data' => null,
    ]);
    $this->assertDatabaseMissing('medications', ['id' => $medication->id]);
});
