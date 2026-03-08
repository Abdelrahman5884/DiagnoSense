<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'name' => $this->name,
            'price' => (float) $this->price,
            'summaries_limit' => $this->summaries_limit,
            'features' => is_string($this->features) ? json_decode($this->features) : $this->features,
            'duration_days' => $this->duration_days,
        ];
    }
}
