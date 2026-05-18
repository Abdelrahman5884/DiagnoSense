<?php

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientLabResult;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('azure');
    $this->doctorUser = createDoctorWithBilling();
    $this->doctor = $this->doctorUser->doctor;
    $this->patient = Patient::factory()->create();
    $this->patient->doctors()->attach($this->doctor->id);

    $this->actingAs($this->doctorUser);
});

it('returns empty data message when no lab results and no active analysis exist', function () {
    $response = $this->getJson(route('patients.comparative-analysis', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'No comparative analysis data available for this patient.')
        ->assertJsonPath('data', null);
});

it('calculates trends and percentages correctly for multiple lab results', function () {
    PatientLabResult::factory()->create([
        'patient_id' => $this->patient->id,
        'standard_name' => 'Hemoglobin',
        'numeric_value' => '10',
        'created_at' => now()->subDays(2),
    ]);

    PatientLabResult::factory()->create([
        'patient_id' => $this->patient->id,
        'standard_name' => 'Hemoglobin',
        'numeric_value' => '12',
        'created_at' => now(),
    ]);

    $response = $this->getJson(route('patients.comparative-analysis', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.analysis.0.test_name', 'Hemoglobin')
        ->assertJsonPath('data.analysis.0.comparison.current_value', 12)
        ->assertJsonPath('data.analysis.0.comparison.previous_value', 10)
        ->assertJsonPath('data.analysis.0.comparison.change_percentage', 20)
        ->assertJsonPath('data.analysis.0.comparison.trend', 'up');
});

it('returns still_processing true when AI is analyzing new reports', function () {
    PatientLabResult::factory()->create(['patient_id' => $this->patient->id]);

    AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing',
    ]);

    $response = $this->getJson(route('patients.comparative-analysis', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', true)
        ->assertJsonPath('message', 'Comparative analysis retrieved successfully.');
});

it('shows historical data and a warning message when the latest analysis fails', function () {
    PatientLabResult::factory()->create([
        'patient_id' => $this->patient->id,
        'standard_name' => 'Blood Sugar',
        'numeric_value' => '100',
    ]);

    AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'failed',
    ]);

    $response = $this->getJson(route('patients.comparative-analysis', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Note: The AI failed to extract data from the latest reports. Showing historical data only.')
        ->assertJsonFragment(['test_name' => 'Blood Sugar']);
});
