<?php

namespace App\Http\Resources;

use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    public function toArray($request)
    {
        $isVisit = $this->resource instanceof Visit;
        $createdAt = Carbon::parse($this->created_at);

        $doctorModel = $isVisit ? $this->doctor : $this->visit?->doctor;
        $doctorName = $doctorModel?->user?->name;

        return [
            'type' => $isVisit ? 'VISIT' : 'TASK',
            'title' => $isVisit ? __('Visit') : (string) $this->title,
            'description' => $isVisit
                ? Carbon::parse($this->next_visit_date)->format('Y-m-d h:i A')
                : (string) $this->description,
            'doctor' => $doctorName ? 'Dr. '.$doctorName : 'N/A',
            'day' => $createdAt->format('d'),
            'month' => $createdAt->format('M'),
            'year' => $createdAt->format('Y'),
        ];
    }
}
