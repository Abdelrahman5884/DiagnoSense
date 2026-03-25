<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
    if (!$this->duration) {
        return 'ACTIVE';
    }

    $endDate = $this->created_at->copy()->addDays($this->duration);

    if (now()->lessThan($endDate)) {
        return 'ACTIVE';
    }

    return 'COMPLETED';
}
}