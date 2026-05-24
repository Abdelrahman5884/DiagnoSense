<?php

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->doctor = createUserWithType('doctor', fake()->safeEmail());
    $this->patient = createUserWithType('patient', fake()->safeEmail());
    actingAs($this->patient);
    $this->doctor->doctor->patients()->attach($this->patient->patient->id);
    $this->visit = createVisit($this->doctor->doctor, $this->patient->patient);
});

it('returns next visit date', function () {
    $response = $this->get(route('next-visit'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'next_visit_date',
            'status',
            'doctor_name',
            'specialization',
            'date',
            'time',
        ],
    ]);
});
