<?php

namespace App\Services;

use App\Jobs\IngestPatientJob;
use App\Models\Patient;
use App\Models\PatientIngestion;
use Illuminate\Support\Facades\Storage;

class ChatbotService
{
    public function __construct(
        private AIGatewayService $aiGatewayService
    ) {}

    public function ask($question, $patientId)
    {
        $patient = Patient::query()->findOrFail($patientId);
        $reports = $patient->reports;
        $hash = hash('sha256', $reports->pluck('file_path')->sort()->implode(','));
        if (! $this->isIngested($patientId, $hash)) {
            $filesData = $reports->groupBy('type')->map(function ($reportsInGroup, $type) {
                return [
                    'type' => $type,
                    'urls' => $reportsInGroup->pluck('file_path')->map(function ($path) {
                        return Storage::disk('azure')->temporaryUrl($path, now()->addMinutes(60));
                    }),
                ];
            })->values()->toArray();

            dispatch(new IngestPatientJob($patientId, auth()->user()->doctor->id, $filesData, $hash, $question));

            return [
                'message' => 'Preparing patient data...',
                'status' => 202,
            ];
        }

        $answer = $this->aiGatewayService->answer($patientId, $question);

        return [
            'message' => $answer,
            'status' => 200,
        ];
    }

    private function isIngested($patientId, $hash)
    {
        $lastIngestion = PatientIngestion::query()
            ->where('patient_id', $patientId)
            ->where('status', 'completed')
            ->latest()
            ->first();
        if (! $lastIngestion || ! hash_equals($lastIngestion->files_hash, $hash)) {
            return false;
        }

        return true;
    }
}
