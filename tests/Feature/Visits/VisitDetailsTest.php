<?php

use App\Models\Medication;
use App\Models\Task;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createUserWithType('doctor', fake()->unique()->safeEmail());
    $this->patient = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->user->doctor->patients()->attach($this->patient->patient->id);
    actingAs($this->user);
    $this->visit = createVisit($this->user->doctor, $this->patient->patient);
    $this->task = Task::create([
        'title' => 'Task 1',
        'description' => 'Task description',
        'visit_id' => $this->visit->id,
    ]);
    $this->medication = Medication::create([
        'name' => 'Paracetamol',
        'dosage' => '100mg',
        'frequency' => 'daily',
        'visit_id' => $this->visit->id,
    ]);
});

it('allows doctor to view visit details', function () {
    $response = $this->get(route('patients.visits.index', ['patient' => $this->patient->patient->id]));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'tasks' => [
                [
                    'id',
                    'title',
                    'description',
                    'notes',
                    'is_completed',
                    'action',
                    'due_date',
                    'created_at',
                    'updated_at',
                ],
            ],
            'medications' => [
                [
                    'id',
                    'name',
                    'dosage',
                    'frequency',
                    'duration',
                    'action',
                    'created_at',
                    'updated_at',
                ],
            ],
            'next_visit_date',
        ],
    ]);
});

it('prevents doctor from viewing visit details of unassigned patient', function () {
    $otherDoctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $response = actingAs($otherDoctor)->get(route('patients.visits.index', ['patient' => $this->patient->patient->id]));
    $response->assertStatus(403);
});
