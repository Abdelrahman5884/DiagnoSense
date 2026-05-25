<?php

use App\Models\MedicalHistory;
use App\Models\Patient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = createDoctorWithBilling(billingMode: 'pay-per-use', balance: 5000);
    $this->doctor = $this->user->doctor;

    $this->patient1 = Patient::factory()->create(['status' => 'critical']);
    $this->patient2 = Patient::factory()->create(['status' => 'stable']);
    $this->patient3 = Patient::factory()->create(['status' => 'stable']);

    $this->doctor->patients()->attach([
        $this->patient1->id,
        $this->patient2->id,
        $this->patient3->id,
    ]);

    MedicalHistory::create([
        'patient_id' => $this->patient1->id,
        'chronic_diseases' => json_encode(['Diabetes', 'Hypertension']),
    ]);

    MedicalHistory::create([
        'patient_id' => $this->patient2->id,
        'chronic_diseases' => json_encode(['Diabetes']),
    ]);

    actingAs($this->user);
});

it('returns correct data structure for the status distribution pie chart', function () {
    $response = getJson(route('dashboard.status-distribution'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_registered_patients', 3)
        ->assertJsonFragment(['status' => 'critical', 'value' => 1, 'percentage' => 33])
        ->assertJsonFragment(['status' => 'stable', 'value' => 2, 'percentage' => 67])
        ->assertJsonFragment(['status' => 'under review', 'value' => 0, 'percentage' => 0]);
});

it('returns correct aggregated top chronic diseases for the bar chart', function () {
    $response = getJson(route('dashboard.top-diseases'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['label', 'value'],
            ],
        ])
        ->assertJsonFragment(['label' => 'Diabetes', 'value' => 2])
        ->assertJsonFragment(['label' => 'Hypertension', 'value' => 1]);
});
