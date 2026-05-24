<?php

namespace App\Services;

use App\Jobs\IngestPatientJob;
use App\Models\Patient;
use App\Models\PatientIngestion;

class ChatbotService
{
    public function __construct(
        private AIGatewayService $aiGatewayService
    ) {}

    public function ask(string $question, Patient $patient): array
    {
        $reports = $patient->reports;
        $hash = hash('sha256', $reports->pluck('file_path')->sort()->implode(','));
        if (! $this->isIngested($patient, $hash)) {
            dispatch(new IngestPatientJob($patient, auth()->user()->doctor->id, $hash, $question));

            return [
                'message' => 'Preparing patient data...',
                'status' => 202,
            ];
        }

        $answer = $this->aiGatewayService->answer($patient, $question);

        return [
            'message' => $answer,
            'status' => 200,
        ];
    }

    private function isIngested(Patient $patient, string $hash): bool
    {
        $lastIngestion = PatientIngestion::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->latest()
            ->first();
        if (! $lastIngestion || ! hash_equals($lastIngestion->file_hash, $hash)) {
            return false;
        }

        return true;
    }
}
