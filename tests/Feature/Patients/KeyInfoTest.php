<?php
use App\Models\Patient;
use App\Models\AiAnalysisResult;
use App\Models\KeyPoint;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('azure');
    $this->doctorUser = createDoctorWithBilling();
    $this->doctor = $this->doctorUser->doctor;
    $this->patient = Patient::factory()->create();
    $this->patient->doctors()->attach($this->doctor->id);

    $this->actingAs($this->doctorUser);
});

it('returns still_processing true when first analysis is running', function () {
    AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing'
    ]);

    $response = $this->getJson(route('patients.key-info', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', true)
        ->assertJsonPath('message', 'AI analysis for key points is still running.');
});

it('returns still_processing false when key points are ready but status is still processing', function () {
    $analysis = AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'processing'
    ]);

    KeyPoint::factory()->create([
        'ai_analysis_result_id' => $analysis->id,
        'priority' => 'high'
    ]);

    $response = $this->getJson(route('patients.key-info', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', false)
        ->assertJsonCount(1, 'data.key_points.high')
        ->assertJsonPath('message', 'Key points retrieved successfully but comparative analysis is still running.');
});

it('shows historical data while new analysis is processing', function () {
    $oldAnalysis = AiAnalysisResult::factory()->create(['patient_id' => $this->patient->id, 'status' => 'completed' , 'created_at' => now()->subHour()]);
    KeyPoint::factory()->create(['ai_analysis_result_id' => $oldAnalysis->id, 'title' => 'Old Info']);

    AiAnalysisResult::factory()->create(['patient_id' => $this->patient->id, 'status' => 'processing' , 'created_at' => now()]);

    $response = $this->getJson(route('patients.key-info', $this->patient));

    $response->assertStatus(200)
        ->assertJsonPath('data.still_processing', true)
        ->assertJsonPath('message', 'Showing old key points. Some files are still being processed.')
        ->assertJsonFragment(['title' => 'Old Info']);
});
