<?php

namespace App\Jobs;

use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Models\PatientLabResult;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ComparativeAnalysis implements ShouldQueue
{
    use Dispatchable , InteractsWithQueue , Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 10;

    public $timeout = 60;

    public function __construct(
        public Patient $patient,
        public AiAnalysisResult $analysis
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::timeout($this->timeout)->post(config('services.ai.url').'comparative', [
                'patient_id' => $this->patient->id,
            ]);

            if ($response->failed()) {
                throw new Exception('AI Server error: ' . $response->status());
            }

            $labResults = $response->json()['data']['lab_results'] ?? [];
            if (empty($labResults)) {
                throw new Exception('No lab results found in AI response.');
            }

            $dataToInsert = collect($labResults)->map(function ($result) {
                return [
                    'patient_id'            => $this->patient->id,
                    'ai_analysis_result_id' => $this->analysis->id,
                    'category'              => $result['category'],
                    'standard_name'         => $result['standard_name'],
                    'numeric_value'         => $result['numeric_value'],
                    'unit'                  => $result['unit'] ?? '',
                    'status'                => $result['status'],
                    'created_at'            => now(),
                ];
            })->toArray();

            DB::transaction(function () use ($dataToInsert) {
                PatientLabResult::insert($dataToInsert);
                $this->analysis->update(['status' => 'completed']);
            });

        } catch (Exception $e) {
            $this->handleFailure($e);
        }
    }

    protected function handleFailure(Exception $e): void
    {
        $this->analysis->update(['status' => 'failed']);
        \Log::error("Comparative Analysis Failed for Analysis #{$this->analysis->id}: " . $e->getMessage());
        throw $e;
    }
}
