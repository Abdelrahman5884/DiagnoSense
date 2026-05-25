<?php

namespace App\Http\Resources\Medication;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicationListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration ?? 'N/A',
        ];
    }
}
