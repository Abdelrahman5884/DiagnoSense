<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_complaint' => $this->current_complaint,
            'is_smoker' => (bool) $this->is_smoker,
            'previous_surgeries' => (bool) $this->previous_surgeries,
            'chronic_diseases' => $this->chronic_diseases,
            'previous_surgeries_name' => $this->previous_surgeries_name,
            'medications' => $this->medications,
            'allergies' => $this->allergies,
            'family_history' => $this->family_history,
        ];
    }
}
