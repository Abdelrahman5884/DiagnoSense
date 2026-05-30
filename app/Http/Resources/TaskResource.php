<?php

namespace App\Http\Resources;

use App\Http\Resources\Visit\NextVisitResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description ?? null,
            'notes' => $this->notes ?? null,
            'is_completed' => $this->is_completed,
            'action' => $this->action,
            'due_date' => $this->visit->next_visit_date ? Carbon::parse($this->visit->next_visit_date)->format('D, M j, Y g:i A') : null,
            'visit' => new NextVisitResource($this->whenLoaded('visit')),
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d'),
        ];
    }
}
