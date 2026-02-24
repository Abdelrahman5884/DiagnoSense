<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastAnalysis = $this->aiAnalysisResults->first();
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'age' => $this->age,
            'status' => $this->status,
            'ai_insight' => $lastAnalysis->ai_insight ?? "No analysis available yet",
            'last_visit' => $this->created_at->format('M d, Y'),
            'next_appointment' => 'Feb 2,2026',
        ];
    }
}
