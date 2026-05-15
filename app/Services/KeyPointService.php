<?php

namespace App\Services;

use App\Models\KeyPoint;
use App\Models\Patient;

class KeyPointService
{
    public function storeManualNote(Patient $patient, array $data): KeyPoint
    {
        $latestAnalysis = $patient->latestAiAnalysisResult;
        if (!$latestAnalysis) {
            throw new \Exception('Cannot add note: No completed analysis found for this patient.', 422);
        }
        return $latestAnalysis->keyPoints()->create([
            'insight'   => $data['insight'],
            'priority'  => $data['priority'],
            'is_ai_generated' => false,
        ]);
    }

}
