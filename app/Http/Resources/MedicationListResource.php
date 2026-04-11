<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MedicationListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'status' => $this->getStatus(),
        ];
    }

    private function getStatus()
    {
        $duration = trim(strtolower($this->dosage ?? ''));

        if (! $duration) {
            return 'ACTIVE';
        }

        $days = 0;

        if (str_contains($duration, 'week')) {
            $days = 7;
        } elseif (str_contains($duration, 'month')) {
            $days = 30;
        } elseif (str_contains($duration, 'day')) {
            $days = 1;
        }

        $endDate = \Carbon\Carbon::parse($this->created_at)->addDays($days);

        return now()->gte($endDate) ? 'COMPLETED' : 'ACTIVE';
    }
}
