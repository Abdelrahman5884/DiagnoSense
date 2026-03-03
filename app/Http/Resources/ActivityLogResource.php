<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->action,
            'message' => $this->description,

            'doctor' => [
                'id' => $this->doctor?->id,
                'name' => $this->doctor?->user?->name,
            ],

            'created_at' => $this->created_at,
            'time_ago' => $this->created_at?->diffForHumans(),
        ];
    }
}