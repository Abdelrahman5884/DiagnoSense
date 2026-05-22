<?php

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->user = createUserWithType('doctor', fake()->unique()->safeEmail());
    $patient1 = createUserWithType('patient', fake()->unique()->safeEmail());
    $patient2 = createUserWithType('patient', fake()->unique()->safeEmail());
    $patient3 = createUserWithType('patient', fake()->unique()->safeEmail());
  actingAs($this->user);
    $this->user->doctor->patients()->attach([
        $patient1->patient->id,
        $patient2->patient->id,
        $patient3->patient->id,
    ]);
    $this->visit1 = createVisit($this->user->doctor, $patient1->patient, today()->setTime(9, 0));
    $this->visit2 = createVisit($this->user->doctor, $patient2->patient, today()->setTime(10, 0));
    $this->visit3 = createVisit($this->user->doctor, $patient3->patient, today()->setTime(11, 0));
});

it('allows doctor to view today\'s visits successfully', function () {
    $response = $this->get(route('dashboard.todayVisits'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'current_attending' => [
                'id',
                'patient_id',
                'name',
                'age',
                'gender',
                'appointment_time',
                'ai_insight' => [
                    'summary',
                ]
            ],
            'full_queue_list' => [
                "*" => [
                        'id',
                        'patient_id',
                        'name',
                        'age',
                        'gender',
                        'appointment_time',
                        'ai_insight' => [
                            'summary',
                        ],
                        'status_tag',
                ]
            ],
            'remaining_count_label'
        ]
    ]);
});

it('assigns Now status to current patient and Waiting to others', function () {
    $response = $this->get(route('dashboard.todayVisits'));
    $response->assertStatus(200);
    $response->assertJsonPath('data.full_queue_list.0.status_tag', 'Now');
    $response->assertJsonPath('data.full_queue_list.1.status_tag', 'Waiting');
    $response->assertJsonPath('data.full_queue_list.2.status_tag', 'Waiting');
});
