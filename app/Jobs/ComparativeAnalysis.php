<?php

namespace App\Jobs;

use App\Models\AiAnalysisResult;
use App\Models\PatientLabResult;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ComparativeAnalysis implements ShouldQueue
{
    use Dispatchable , InteractsWithQueue , Queueable, SerializesModels;

    public $patientId;

    public $analysisId;

    public $tries = 3;

    public $backoff = 10;

    public $timeout = 60;

    public function __construct($patientId, $analysisId)
    {
        $this->patientId = $patientId;
        $this->analysisId = $analysisId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::timeout($this->timeout)->post(config('services.ai.url').'comparative', [
                'patient_id' => $this->patientId,
            ]);
            if ($response->failed()) {
                AiAnalysisResult::where('id', $this->analysisId)
                    ->update(['status' => 'failed']);
                throw new Exception('AI Server returned an error: '.$response->status());
            }

            $labResults = $response->json()['data']['lab_results'] ?? [];
            if (empty($labResults)) {
                AiAnalysisResult::where('id', $this->analysisId)
                    ->update(['status' => 'failed']);
                throw new Exception('No lab results found in AI response.');
            }

            foreach ($labResults as $result) {
                PatientLabResult::create([
                    'patient_id' => $this->patientId,
                    'ai_analysis_result_id' => $this->analysisId,
                    'category' => $result['category'],
                    'standard_name' => $result['standard_name'],
                    'numeric_value' => $result['numeric_value'],
                    'unit' => $result['unit'] ?? '',
                    'status' => $result['status'],
                    'created_at' => now(),
                ]);
            }
            AiAnalysisResult::where('id', $this->analysisId)
                ->update(['status' => 'completed']);
        } catch (Exception $e) {
            AiAnalysisResult::where('id', $this->analysisId)
                ->update(['status' => 'failed']);
            throw $e;
        }
    }
}
