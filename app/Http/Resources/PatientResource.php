<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestVisit = $this->patient->latestVisit;

        return [
            'id' => $this->patient->id,
            'name' => $this->name,
            'age' => $this->patient->age ?? 'N/A',
            'status' => $this->patient->status,
            'ai_insight' => $this->patient->latestAiAnalysisResult->ai_insight ?? 'No analysis available yet',
            'last_visit' => $latestVisit
                ? $latestVisit->created_at->format('M d, Y')
                : 'No visits yet',
            'next_appointment' => $latestVisit && $latestVisit->next_visit_date
                ? $latestVisit->next_visit_date->format('M d, Y')
                : 'Not scheduled',
        ];
    }
}
