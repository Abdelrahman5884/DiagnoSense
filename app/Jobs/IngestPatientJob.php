<?php

namespace App\Jobs;

use App\Events\ChatbotAnswerFailed;
use App\Events\ChatbotAnswerReady;
use App\Models\Patient;
use App\Models\PatientIngestion;
use App\Services\AIGatewayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class IngestPatientJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

    public function __construct(
        public $patientId,
        public $doctorId,
        public $hash,
        public $question
    ) {}

    public function handle(AIGatewayService $aiGatewayService)
    {
        $patient = Patient::query()->findOrFail($this->patientId);
        $filesData = $patient->reports->groupBy('type')->map(function ($reportsInGroup, $type) {
            return [
                'type' => $type,
                'urls' => $reportsInGroup->pluck('file_path')->map(function ($path) {
                    return Storage::disk('azure')->temporaryUrl($path, now()->addMinutes(60));
                }),
            ];
        })->values()->toArray();
        try {
            $aiGatewayService->ingest($this->patientId, $filesData);
            PatientIngestion::query()->create([
                'patient_id' => $this->patientId,
                'status' => 'completed',
                'files_hash' => $this->hash,
            ]);
        } catch (\Exception $e) {
            PatientIngestion::query()->create([
                'patient_id' => $this->patientId,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'files_hash' => null,
            ]);
            throw $e;
        }

        try {
            $answer = $aiGatewayService->answer($this->patientId, $this->question);
            event(new ChatbotAnswerReady($this->doctorId, $answer));
        } catch (\Exception $e) {
            event(new ChatbotAnswerFailed($this->doctorId, 'Failed to get answer from chatbot'));
        }
    }
}
