<?php

namespace App\Jobs;

use App\Models\AiAnalysisResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessAi implements ShouldQueue
{
    use Dispatchable , InteractsWithQueue , Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 3;

    public $backoff = 60;

    protected $analysisId;

    protected $jobData;

    /**
     * Create a new job instance.
     */
    public function __construct($analysisId, $jobData)
    {
        $this->analysisId = $analysisId;
        $this->jobData = $jobData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $analysisRecord = AiAnalysisResult::find($this->analysisId);
        if (! $analysisRecord) {
            return;
        }

        try {
            $analysisRecord->update(['status' => 'processing']);
            $ApiData = [
                'medical_pdf_urls' => $this->generateUrls('medical_history'),
                'lab_pdf_urls' => $this->generateUrls('lab'),
                'radiology_pdf_urls' => $this->generateUrls('radiology'),
                'medical_form' => [
                    'smoker' => (bool) ($this->jobData['history']['is_smoker'] ?? false),
                    'age' => (int) ($this->jobData['age'] ?? 0),
                    'gender' => (string) ($this->jobData['gender'] ?? 'unknown'),
                    'chronic_diseases' => $this->jobData['history']['chronic_diseases'] ?? '',
                    'previous_surgeries' => $this->jobData['history']['previous_surgeries'] ?? '',
                    'previous_surgeries_name' => $this->jobData['history']['previous_surgeries_name'] ?? '',
                    'medications' => $this->jobData['history']['medications'] ?? '',
                    'allergies' => $this->jobData['history']['allergies'] ?? '',
                    'family_history' => $this->jobData['history']['family_history'] ?? '',
                    'current_complaint' => $this->jobData['history']['current_complaint'] ?? '',
                ],
                'decision_support' => false,
            ];

            $response = Http::timeout($this->timeout)->post(config('services.ai.url'), $ApiData);

            if ($response->successful()) {
                $data = $response->json();

                $insight = $data['key_information']['ai_insight'] ?? null;
                $summary = $data['key_information']['ai_summary'] ?? null;

                unset($data['key_information']['ai_insight']);
                unset($data['key_information']['ai_summary']);

                $analysisRecord->update([
                    'ai_insight' => $insight,
                    'ai_summary' => $summary,
                    'response' => $data,
                    'status' => 'completed',
                ]);

                foreach (['high_priority_alerts', 'medium_priority_alerts', 'low_priority_alerts'] as $type) {
                    $alerts = $data['key_information'][$type] ?? [];
                    foreach ($alerts as $item) {
                        $analysisRecord->keyPoints()->create([
                            'priority' => str_replace('_priority_alerts', '', $type),
                            'title' => $item['title'],
                            'insight' => $item['insight'],
                            'evidence' => $item['evidence'],
                        ]);
                    }
                }

            } else {
                $analysisRecord->update([
                    'response' => ['error' => 'AI analysis failed', 'details' => $response->body()],
                    'status' => 'failed',
                ]);
            }
        } catch (\Exception $e) {
            $analysisRecord->update([
                'response' => ['error' => 'AI analysis failed', 'details' => $e->getMessage()],
                'status' => 'failed',
            ]);
        }
    }

    private function generateUrls($type)
    {
        $urls = [];
        $paths = $this->jobData['file_paths'][$type] ?? [];

        foreach ($paths as $path) {
            $urls[] = Storage::disk('azure')->temporaryUrl($path, now()->addMinutes(60));
        }

        return $urls;
    }
}
