<?php

use App\Jobs\AiAnalysisJob;
use App\Models\AiAnalysisResult;
use App\Models\MedicalHistory;
use App\Services\AiAnalysisBillingService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Storage::fake('azure');
    $user = createDoctorWithBilling();
    $this->aiAnalysisResult = AiAnalysisResult::factory()->create();
    $this->medicalHistory = MedicalHistory::factory()->create(['patient_id' => $this->aiAnalysisResult->patient_id]);
    $this->jobData = [
        'patient_id' => $this->aiAnalysisResult->patient_id,
        'doctor_id' => $user->doctor->id,
        'age' => $this->aiAnalysisResult->patient->age,
        'gender' => $this->aiAnalysisResult->patient->gender,
        'history' => $this->medicalHistory->toArray(),
        'file_paths' => [
            'lab' => ['path/to/lab/report.pdf'],
            'radiology' => ['path/to/radiology/report.pdf'],
            'medical_history' => ['path/to/medical/history/report.pdf'],
        ],
        'features' => [
            'decision_support' => true,
        ],
    ];
});

function assertDecisionSupportsStored(array $decisions): void
{
    foreach ($decisions as $decision) {
        test()->assertDatabaseHas('decision_supports', $decision);
    }
}

function assertKeyPointsStored(array $keyPoints): void
{
    foreach ($keyPoints as $keyPoint) {
        test()->assertDatabaseHas('key_points', $keyPoint);
    }
}

it('update analysis result when AI response successfully', function () {
    Http::fake([config('services.ai.url').'analyze' => Http::response(fakeAiResponse())]);
    $job = new AiAnalysisJob($this->aiAnalysisResult->id, $this->jobData);
    $job->handle(new AiAnalysisBillingService);
    $this->assertDatabaseHas('ai_analysis_results', [
        'id' => $this->aiAnalysisResult->id,
        'ai_insight' => 'Test AI Insight',
        'ai_summary' => 'Test AI Summary',
        'status' => 'processing',
        'ocr_file_path' => 'path/to/ocr/report.pdf',
    ]);
    assertDecisionSupportsStored([
        ['condition' => 'Condition 1', 'probability' => 0.8, 'status' => 'Positive', 'clinical_reasoning' => 'Clinical Reasons 1'],
        ['condition' => 'Condition 2', 'probability' => 0.6, 'status' => 'Negative', 'clinical_reasoning' => 'Clinical Reasons 2'],
    ]);
    assertKeyPointsStored([
        ['priority' => 'high', 'title' => 'High Priority Alert 1', 'insight' => 'Insight 1', 'evidence' => json_encode(['Evidence 1', 'Evidence 2'])],
        ['priority' => 'medium', 'title' => 'Medium Priority Alert 1', 'insight' => 'Insight 1', 'evidence' => json_encode(['Evidence 1', 'Evidence 2'])],
        ['priority' => 'low', 'title' => 'Low Priority Alert 1', 'insight' => 'Insight 1', 'evidence' => json_encode(['Evidence 1', 'Evidence 2'])],
    ]);
});

it('update analysis result when AI response fails', function () {
    Http::fake([config('services.ai.url').'analyze' => Http::response(['error' => 'AI analysis failed'], 500)]);
    $job = new AiAnalysisJob($this->aiAnalysisResult->id, $this->jobData);
    expect(fn () => $job->handle(new AiAnalysisBillingService))->toThrow(\Exception::class);
    $this->assertDatabaseHas('ai_analysis_results', [
        'id' => $this->aiAnalysisResult->id,
        'status' => 'failed',
    ]);
});
