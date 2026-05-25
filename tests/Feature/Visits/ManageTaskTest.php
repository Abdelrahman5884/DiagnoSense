<?php

use App\Models\Visit;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = createUserWithType('doctor', fake()->unique()->safeEmail());
    $this->patient = createUserWithType('patient', fake()->unique()->safeEmail());
    $this->user->doctor->patients()->attach($this->patient->patient->id);
    actingAs($this->user);
    $this->visit = createVisit($this->user->doctor, $this->patient->patient);

    $this->task = [
        'title' => 'Task 1',
        'description' => 'Task description',
        'action' => 'save',
    ];
});

it('allows doctor to add task to visit successfully', function () {
    $response = $this->post(route('visits.tasks.store', ['visit' => $this->visit->id]), $this->task);
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
    $this->assertDatabaseHas('tasks', [
        'title' => $this->task['title'],
        'visit_id' => $this->visit->id,
    ]);
    $this->assertDatabaseHas('visits', [
        'id' => $this->visit->id,
        'status' => 'completed',
    ]);
});

it('prevents doctor from adding task to unassigned patient', function () {
    $otherDoctor = createUserWithType('doctor', fake()->unique()->safeEmail());
    $response = actingAs($otherDoctor)->post(route('visits.tasks.store', ['visit' => $this->visit->id]), $this->task);
    $response->assertStatus(403);
});

it('denies task creation if next visit date is missing', function () {
    $otherVisit = Visit::create([
        'next_visit_date' => null,
        'patient_id' => $this->patient->patient->id,
        'doctor_id' => $this->user->doctor->id,
        'status' => 'draft',
    ]);
    $response = $this->post(route('visits.tasks.store', ['visit' => $otherVisit->id]), $this->task);
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Next visit date is required for tasks.',
    ]);
});

it('allows doctor to delete task successfully', function () {
    $task = $this->visit->tasks()->create($this->task);
    $response = $this->delete(route('tasks.destroy', ['task' => $task->id]));
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Task deleted successfully',
        'data' => null,
    ]);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});
