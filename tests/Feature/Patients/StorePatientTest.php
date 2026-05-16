<?php

use App\Jobs\AiAnalysisJob;
use App\Models\MedicalHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();
    Http::fake();
    Storage::fake('azure');
    $user = createDoctorWithBilling();
    actingAs($user);
    $this->validPatientData = validPatientData();
});

it('allow doctor to create patient successfully', function () {
    $response = $this->postJson(route('patients.store'), $this->validPatientData);
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            'patient_id',
            'analysis_result_id',
        ],
    ]);
    $this->assertDatabaseHas('users', [
        'contact' => $this->validPatientData['contact'],
        'type' => 'patient',
    ]);
    $this->assertDatabaseHas('patients', [
        'date_of_birth' => $this->validPatientData['date_of_birth'],
        'gender' => $this->validPatientData['gender'],
        'national_id' => $this->validPatientData['national_id'],
    ]);
    $this->assertDatabaseHas('medical_histories', [
        'patient_id' => $response->json('data.patient_id'),
        'is_smoker' => $this->validPatientData['is_smoker'],
        'previous_surgeries_name' => $this->validPatientData['previous_surgeries_name'],
        'current_medications' => $this->validPatientData['current_medications'],
        'allergies' => $this->validPatientData['allergies'],
        'family_history' => $this->validPatientData['family_history'],
        'current_complaints' => $this->validPatientData['current_complaints'],
    ]);
    $medicalHistory = MedicalHistory::where(
        'patient_id',
        $response->json('data.patient_id')
    )->first();

    expect($medicalHistory->chronic_diseases)
        ->toBe($this->validPatientData['chronic_diseases']);

    foreach (['lab', 'radiology', 'medical_history'] as $reportType) {
        $this->assertDatabaseHas('reports', [
            'patient_id' => $response->json('data.patient_id'),
            'type' => $reportType,
            'file_name' => $this->validPatientData[$reportType][0]->getClientOriginalName(),
            'mime_type' => $this->validPatientData[$reportType][0]->getMimeType(),
        ]);
    }
    $this->assertDatabaseHas('doctor_patient', [
        'doctor_id' => auth()->user()->doctor->id,
        'patient_id' => $response->json('data.patient_id'),
    ]);
    Queue::assertPushed(AiAnalysisJob::class);
    $this->assertDatabaseHas('ai_analysis_results', [
        'patient_id' => $response->json('data.patient_id'),
        'status' => 'processing',
    ]);
});

it('if fails validation when contact or files are invalid', function (array $invalidData, array $expectedErrors) {
    $response = $this->postJson(route('patients.store'), $invalidData);
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Validation Errors',
        'data' => $expectedErrors,
    ]);
})->with([
    'invalid contact' => [fn () => array_merge(validPatientData(), ['contact' => 'invalid']), ['contact' => ['The contact must be a valid email address or a valid phone number starting with 010, 011, 012, or 015 followed by 8 digits.']]],
    'no files' => [[array_diff_key(validPatientData(), ['lab' => [], 'radiology' => [], 'medical_history' => []])], ['lab' => ['Please upload at least one lab test result or radiology report or medical history report.'], 'radiology' => ['Please upload at least one lab test result or radiology report or medical history report.'], 'medical_history' => ['Please upload at least one lab test result or radiology report or medical history report.']]],
]);

it('if fails validation when contact is already taken', function () {
    createUserWithType('patient', $this->validPatientData['contact']);
    $response = $this->postJson(route('patients.store'), $this->validPatientData);
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Validation Errors',
        'data' => ['contact' => ['The contact has already been taken.']],
    ]);
});
