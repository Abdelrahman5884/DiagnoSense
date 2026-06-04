<?php

namespace App\Http\Resources\Visit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentVisitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient->id,
            'name' => $this->patient->user->name,
            'age' => $this->patient->age.' y/o',
            'gender' => ucfirst($this->patient->gender),
            'appointment_time' => Carbon::parse($this->next_visit_date)->format('h:i A'),
            'ai_insight' => [
                'summary' => $this->patient->latestAiAnalysisValue('ai_insight') ?? 'No AI insight found for this patient.',
            ],
        ];
    }
}
