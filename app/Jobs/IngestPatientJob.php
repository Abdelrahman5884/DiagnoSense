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
        public Patient $patient,
        public int  $doctorId,
        public string $hash,
        public string $question
    ) {}

    public function handle(AIGatewayService $aiGatewayService): void
    {
        try {
            $aiGatewayService->ingest($this->patient);
            PatientIngestion::query()->create([
                'patient_id' => $this->patient->id,
                'status' => 'completed',
                'files_hash' => $this->hash,
            ]);
        } catch (\Exception $e) {
            PatientIngestion::query()->create([
                'patient_id' => $this->patient->id,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'files_hash' => null,
            ]);
            throw $e;
        }

        try {
            $answer = $aiGatewayService->answer($this->patient, $this->question);
            event(new ChatbotAnswerReady($this->doctorId, $answer));
        } catch (\Exception $e) {
            event(new ChatbotAnswerFailed($this->doctorId, 'Failed to get answer from chatbot'));
        }
    }
}
