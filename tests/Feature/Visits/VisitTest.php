<?php

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $userDoctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $userPatient = createUserWithType('patient', fake()->unique()->safeEmail());
    $doctor = $userDoctor->doctor;
    $this->patient = $userPatient->patient;
    $doctor->patients()->attach($this->patient);
    actingAs($userDoctor);
    $this->visit = createVisit($doctor, $this->patient, today());
});

it('allow doctor to create visit successfully', function () {
    $date = now()->addDays(7)->toDateTimeString();
    $response = $this->post(route('patients.visits.store', ['patient' => $this->patient->id]), [
        'has_next_visit' => true,
        'next_visit_date' => $date,
        'action' => 'save',
    ]);
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
    $this->assertDatabaseHas('visits', [
        'patient_id' => $this->patient->id,
        'doctor_id' => auth()->user()->doctor->id,
        'next_visit_date' => $date,
        'status' => 'completed',
    ]);
});

it('prevents doctor from creating visit for unassigned patient', function () {
    $otherDoctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $response = actingAs($otherDoctor)->post(route('patients.visits.store', ['patient' => $this->patient->id]), [
        'has_next_visit' => true,
        'next_visit_date' => now()->addDays(7)->toDateTimeString(),
        'action' => 'save',
    ]);
    $response->assertStatus(403);
});

it('allows doctor to mark visit as attended successfully', function () {
    $response = $this->patch(route('visits.attend', ['visit' => $this->visit->id]));
    $response->assertStatus(200);
    $this->assertDatabaseHas('visits', [
        'id' => $this->visit->id,
        'status' => 'attended',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
    ]);
});
