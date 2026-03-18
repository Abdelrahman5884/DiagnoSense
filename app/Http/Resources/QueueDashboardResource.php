<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentId = $request->input('current_id');

        return [
            'id'               => $this->id,
            'name'             => $this->user->name,
            'age'              => $this->age,
            'gender'           => ucfirst($this->gender),
            'appointment_time' => Carbon::parse($this->next_visit_date)->format('h:i A'),
            'status_tag'       => $this->id == $currentId ? 'Now' : 'Waiting',
        ];
    }
}
