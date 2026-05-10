<?php

use App\Models\AiAnalysisResult;
use App\Models\DecisionSupport;
use App\Models\Patient;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('azure');
    $this->doctorUser = createDoctorWithBilling();
    $this->doctor = $this->doctorUser->doctor;
    $this->patient = Patient::factory()->create();
    $this->patient->doctors()->attach($this->doctor->id);

    $this->actingAs($this->doctorUser);
});

it('returns is_loading_new_decisions true when first analysis is running', function () {
    AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing',
    ]);

    $response = $this->getJson(route('patients.decision-support', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', true)
        ->assertJsonPath('message', 'AI analysis for decision support is still running.');
});

it('returns is_loading_new_decisions false when decisions are ready but status is still processing', function () {
    $analysis = AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing',
    ]);

    DecisionSupport::factory()->create([
        'ai_analysis_result_id' => $analysis->id,
        'condition' => 'Initial Diagnosis',
    ]);

    $response = $this->getJson(route('patients.decision-support', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', false)
        ->assertJsonCount(1, 'data.decisions')
        ->assertJsonPath('message', 'decision support retrieved successfully but comparative analysis is still running.');
});

it('shows historical decisions while new analysis is processing', function () {
    $oldAnalysis = AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'completed',
        'created_at' => now()->subHour()
    ]);
    DecisionSupport::factory()->create([
        'ai_analysis_result_id' => $oldAnalysis->id,
        'condition' => 'Old Condition'
    ]);

    AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing',
        'created_at' => now()
    ]);

    $response = $this->getJson(route('patients.decision-support', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', true)
        ->assertJsonPath('message', 'Showing old decision support. Some files are still being processed.')
        ->assertJsonFragment(['condition' => 'Old Condition']);
});
