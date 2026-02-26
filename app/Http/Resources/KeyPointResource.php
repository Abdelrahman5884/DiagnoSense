<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KeyPointResource extends JsonResource
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
            'priority' => $this->priority,
            'title' => $this->title,
            'insight' => $this->insight,
            'evidence' => $this->evidence,
            'date' => $this->created_at->format('M d, Y'),
        ];
    }
}
