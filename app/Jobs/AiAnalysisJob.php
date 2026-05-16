<?php

namespace App\Jobs;

use App\Helpers\FileSystem;
use App\Models\AiAnalysisResult;
use App\Models\Doctor;
use App\Services\AiAnalysisBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class AiAnalysisJob implements ShouldQueue
{
    use Dispatchable , InteractsWithQueue , Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $analysisId,
        protected array $jobData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiAnalysisBillingService $billingService): void
    {
        $analysisRecord = AiAnalysisResult::find($this->analysisId);
        if (! $analysisRecord) {
            return;
        }

        try {
            $apiData = $this->prepareMedicalData();

            $response = Http::timeout($this->timeout)->post(config('services.ai.url').'analyze', $apiData);

            if ($response->successful()) {
                $this->processAiAnalysisResponse($billingService, $response->json(), $analysisRecord);
            } else {
                $this->handleFailedAnalysis($analysisRecord, $response->body());
                throw new \Exception('AI analysis failed with status '.$response->status());
            }
        } catch (\Exception $e) {
            $this->handleFailedAnalysis($analysisRecord, $e->getMessage());
            throw $e;
        }
    }

    private function generateUrls(string $type): array
    {
        $paths = $this->jobData['file_paths'][$type] ?? [];

        return array_map(fn ($path) => FileSystem::getTempUrl($path), $paths);
    }

    private function updateAnalysisResult(
        AiAnalysisResult $analysisRecord,
        ?string $insight,
        ?string $summary,
        mixed $data,
        bool $hasLabFiles,
        ?string $ocr_file_path
    ): void {
        $analysisRecord->update([
            'ai_insight' => $insight,
            'ai_summary' => $summary,
            'response' => $data,
            'status' => $hasLabFiles && ! ($this->jobData['isReAnalysis'] ?? false) ? 'processing' : 'completed',
            'ocr_file_path' => $ocr_file_path,
        ]);
    }

    private function getDoctor(): ?Doctor
    {
        return Doctor::with(['activeSubscription', 'wallet', 'user'])->find($this->jobData['doctor_id']);
    }

    private function processAiAnalysisResponse(
        AiAnalysisBillingService $billingService,
        array $data,
        AiAnalysisResult $analysisRecord
    ): void {
        $insight = $data['key_information']['ai_insight'] ?? null;
        $summary = $data['key_information']['ai_summary'] ?? null;
        $hasLabFiles = ! empty($this->jobData['file_paths']['lab']);
        $ocr_file_path = $data['pdf_path'] ?? null;
        $this->updateAnalysisResult($analysisRecord, $insight, $summary, $data, $hasLabFiles, $ocr_file_path);

        if ($this->jobData['features']['decision_support']) {
            $data = $this->storeDecisionSupports($data, $analysisRecord);
        }

        unset($data['key_information']['ai_insight'], $data['key_information']['ai_summary']);

        $isReAnalysis = $this->jobData['isReAnalysis'] ?? false;
        if (! $isReAnalysis) {
            $this->storeKeyPoints($data['key_information'], $analysisRecord);
        }
        $doctor = $this->getDoctor();

        if ($doctor) {
            $billingService->handleBilling($doctor, $analysisRecord);
        }
    }

    private function storeDecisionSupports(array $data, AiAnalysisResult $analysisRecord): array
    {
        $decisions = $data['decision_support'] ?? [];
        unset($data['decision_support']);
        foreach ($decisions as $decision) {
            $analysisRecord->decisionSupports()->create([
                'condition' => $decision['condition'],
                'probability' => $decision['probability'],
                'status' => $decision['status'],
                'clinical_reasoning' => $decision['clinical_reasoning'],
            ]);
        }

        return $data;
    }

    private function storeKeyPoints(array $key_information, AiAnalysisResult $analysisRecord): void
    {
        foreach (['high_priority_alerts', 'medium_priority_alerts', 'low_priority_alerts'] as $type) {
            $alerts = $key_information[$type] ?? [];
            foreach ($alerts as $item) {
                $analysisRecord->keyPoints()->create([
                    'priority' => str_replace('_priority_alerts', '', $type),
                    'title' => $item['title'],
                    'insight' => $item['insight'],
                    'evidence' => $item['evidence'],
                    'is_ai_generated' => true,
                ]);
            }
        }
    }

    private function prepareMedicalData(): array
    {
        return [
            'patient_id' => $this->jobData['patient_id'],
            'medical_pdf_urls' => $this->generateUrls('medical_history'),
            'lab_pdf_urls' => $this->generateUrls('lab'),
            'radiology_pdf_urls' => $this->generateUrls('radiology'),
            'medical_form' => [
                'smoker' => (bool) ($this->jobData['history']['is_smoker'] ?? false),
                'age' => (int) ($this->jobData['age'] ?? 0),
                'gender' => (string) ($this->jobData['gender'] ?? 'unknown'),
                'chronic_diseases' => $this->jobData['history']['chronic_diseases'] ?? '',
                'previous_surgeries' => (bool) $this->jobData['history']['previous_surgeries_name'],
                'previous_surgeries_name' => $this->jobData['history']['previous_surgeries_name'] ?? '',
                'current_medications' => $this->jobData['history']['current_medications'] ?? '',
                'allergies' => $this->jobData['history']['allergies'] ?? '',
                'family_history' => $this->jobData['history']['family_history'] ?? '',
                'current_complaint' => $this->jobData['history']['current_complaint'] ?? '',
            ],
            'decision_support' => (bool) ($this->jobData['features']['decision_support'] ?? false),
        ];
    }

    private function handleFailedAnalysis(AiAnalysisResult $analysisRecord, string $message): void
    {
        $analysisRecord->update([
            'response' => ['error' => 'AI analysis failed', 'details' => $message],
            'status' => 'failed',
        ]);
    }
}
