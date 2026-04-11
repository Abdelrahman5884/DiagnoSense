<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIGatewayService
{
    public function ingest($patientId, $filesData)
    {
        Http::timeout(config('services.ai.ingest_timeout'))->post(config('services.ai.url').'ingest-patient-data', [
            'patient_id' => $patientId,
            'files_data' => $filesData,
        ])->throw();
    }

    public function answer($patientId, $question)
    {
        $answer = Http::timeout(config('services.ai.answer_timeout'))->post(config('services.ai.url').'/query', [
            'patient_id' => $patientId,
            'question' => $question,
        ])->throw();

        return $answer->json('answer');
    }
}
