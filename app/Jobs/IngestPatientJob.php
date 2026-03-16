<?php

namespace App\Jobs;

use App\Events\ChatbotAnswerFailed;
use App\Events\ChatbotAnswerReady;
use App\Models\PatientIngestion;
use App\Services\AIGatewayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IngestPatientJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

    public function __construct(
        public $patientId,
        public $doctorId,
        public $filesData,
        public $hash,
        public $question
    ) {}

    public function handle(AIGatewayService $aiGatewayService)
    {
        try {
            $aiGatewayService->ingest($this->patientId, $this->filesData);
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
