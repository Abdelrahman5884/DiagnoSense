<?php

namespace App\Http\Resources;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $history = $this->medicalHistory;
        $latestAiAnalysis = $this->latestAiAnalysisResult;
        return [
            "patientId" => $this->national_id,
            "patientName" => $this->user->name,
            "doctorName" => Auth::user()->name,
            'smart_summary' => $latestAiAnalysis?->ai_summary ?? "No AI analysis generated for this patient yet.",
            "age" => $this->age,
            "smoker" => $history?->is_smoker ? 'Yes' : 'No',
            "chronicDiseases" => $history?->chronic_diseases ?? 'N/A',
            "previousSurgeries" => $history?->previous_surgeries ? $history->previous_surgeries_name : 'N/A',
            "allergies" => $history?->allergies ?? 'N/A',
            "medications" => $history?->medications ?? 'N/A',
            "familyHistory" => $history?->family_history ?? 'N/A',
            "status" => $this->status,
        ];
    }
}
