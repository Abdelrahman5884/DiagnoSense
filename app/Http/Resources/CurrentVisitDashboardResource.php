<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentVisitDashboardResource extends JsonResource
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
            'name' => $this->user->name,
            'age' => $this->age.' y/o',
            'gender' => ucfirst($this->gender),
            'appointment_time' => Carbon::parse($this->next_visit_date)->format('h:i A'),
            'ai_insight' => [
                'summary' => $this->latestAiAnalysisResult?->ai_insight ?? 'No AI insight found for this patient.',
            ],
        ];
    }
}
