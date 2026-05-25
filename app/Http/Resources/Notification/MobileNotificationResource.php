<?php

namespace App\Http\Resources\Notification;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileNotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $createdAt = Carbon::parse($this->created_at);

        return [
            'id' => $this->id,
            'type' => strtoupper($this->data['type'] ?? 'UNKNOWN'),
            'title' => $this->data['title'] ?? '',
            'body' => $this->data['description'] ?? '',
            'time_ago' => $createdAt->diffForHumans(),
        ];
    }

}
