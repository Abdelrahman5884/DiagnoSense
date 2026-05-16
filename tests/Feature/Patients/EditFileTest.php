<?php

use App\Jobs\AiAnalysisJob;
use App\Jobs\ComparativeAnalysis;
use App\Models\AiAnalysisResult;
use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('azure');
    Bus::fake();
    $this->doctorUser = createDoctorWithBilling();
    $this->doctor = $this->doctorUser->doctor;
    $this->patient = Patient::factory()->create();
    $this->patient->doctors()->attach($this->doctor->id);
    $this->medicalHistory = MedicalHistory::factory()->create([
        'patient_id' => $this->patient->id,
        'current_complaints' => 'Old complaint description',
    ]);

    $this->actingAs($this->doctorUser);
});

it('updates patient profile details successfully without triggering AI when data is basic', function () {
    $payload = [
        'name' => 'Menna Baligh Updated',
        'contact' => 'menna.updated@example.com',
        'gender' => 'female',
        'date_of_birth' => '2003-12-23',
        'current_complaints' => 'Old complaint description',
    ];

    $response = $this->patchJson(route('patients.update', $this->patient), $payload);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Patient file updated successfully');

    $this->assertDatabaseHas('users', ['name' => 'Menna Baligh Updated', 'contact' => 'menna.updated@example.com']);
    $this->assertDatabaseHas('patients', ['id' => $this->patient->id, 'gender' => 'female']);

    Bus::assertNotDispatched(AiAnalysisJob::class);
});

it('triggers AI analysis during update if the current complaints field changes', function () {
    $payload = [
        'name' => 'Menna Baligh',
        'contact' => 'menna@example.com',
        'gender' => 'female',
        'date_of_birth' => '2003-12-23',
        'current_complaints' => 'New Completely Different Complaint',
    ];

    $response = $this->patchJson(route('patients.update', $this->patient), $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('ai_analysis_results', [
        'patient_id' => $this->patient->id,
        'status' => 'processing',
    ]);

    Bus::assertDispatched(AiAnalysisJob::class);
});

it('upgrades and re-analyzes patient successfully when an old analysis exists', function () {
    $existingAnalysis = AiAnalysisResult::factory()->create([
        'patient_id' => $this->patient->id,
        'status' => 'completed',
    ]);

    $response = $this->postJson(route('patients.re-analyze', $this->patient));
    $response->dump();
    $response->assertStatus(200)
        ->assertJsonPath('message', 'AI Is Processing Now Due To Upgrade')
        ->assertJsonPath('data.analysis_id', $existingAnalysis->id);

    expect($existingAnalysis->fresh()->status)->toBe('processing');
    expect(AiAnalysisResult::where('patient_id', $this->patient->id)->count())->toBe(1);

    Bus::assertDispatched(AiAnalysisJob::class);
    Bus::assertNotDispatched(ComparativeAnalysis::class);
});
