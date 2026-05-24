<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Http;

class AIGatewayService
{
    public function ingest(Patient $patient): void
    {
        Http::timeout(config('services.ai.ingest_timeout'))->post(config('services.ai.url').'ingest-patient-data', [
            'patient_id' => $patient->id,
        ])->throw();
    }

    public function answer(Patient $patient, string $question): string
    {
        $answer = Http::timeout(config('services.ai.answer_timeout'))->post(config('services.ai.url').'/query', [
            'patient_id' => $patient->id,
            'question' => $question,
        ])->throw();

        return $answer->json('answer');
    }
}
