<?php

use App\Models\Task;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->doctor = createUserWithType('doctor', fake()->safeEmail());
    $this->patient = createUserWithType('patient', fake()->safeEmail());
    actingAs($this->patient);
    $this->doctor->doctor->patients()->attach($this->patient->patient->id);
    $this->visit = createVisit($this->doctor->doctor, $this->patient->patient);
    $this->task1 = Task::create([
        'title' => 'Task 1',
        'description' => 'Task description',
        'visit_id' => $this->visit->id,
    ]);
    $this->task2 = Task::create([
        'title' => 'Task 2',
        'description' => 'Task description',
        'visit_id' => $this->visit->id,
    ]);
});

it('allows patient to view their tasks', function () {
    $response = $this->get(route('tasks.index'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => [
                'id',
                'title',
                'description',
                'notes',
                'is_completed',
                'action',
                'due_date',
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
        ],
    ]);
});

it('allows patient to view task details', function () {
    $response = $this->get(route('tasks.show', ['task' => $this->task1->id]));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'id',
            'title',
            'description',
            'notes',
            'is_completed',
            'action',
            'due_date',
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
});

it('prevents patient from viewing task details that are not assigned to them', function () {
    $otherPatient = createUserWithType('patient', fake()->unique()->safeEmail());
    $response = actingAs($otherPatient)->get(route('tasks.show', ['task' => $this->task1->id]));
    $response->assertStatus(403);
});

it('allows patient to mark task as completed', function () {
    $response = $this->patch(route('tasks.complete', ['task' => $this->task1->id]));
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Task marked as completed',
    ]);
    $this->assertDatabaseHas('tasks', [
        'id' => $this->task1->id,
        'is_completed' => true,
    ]);
});
