<?php

namespace App\Http\Resources\Visit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class NextVisitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->doctor;

        return [
            'id' => $this->id,
            'next_visit_date' => $this->next_visit_date ? Carbon::parse($this->next_visit_date)->format('D, M j, Y g:i A') : 'No next visit',
            'status' => $this->status,
            'doctor_name' => $doctor?->user?->name,
            'specialization' => $doctor?->specialization,
            'date' => $this->next_visit_date?->format('d M, Y'),
            'time' => $this->next_visit_date?->format('h:i A'),
        ];
    }
}
