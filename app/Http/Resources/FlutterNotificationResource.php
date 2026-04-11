<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FlutterNotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'time' => $this->getTime(),
            'type' => $this->getType(), 
        ];
    }

    private function getTitle()
    {
       
        if (isset($this['title'])) {
            return $this['title'];
        }

        return match ($this->model_type) {
            'Task' => 'Task',
            'Visit' => 'New Visit',
            'Medication' => 'Medication',
            default => 'Update',
        };
    }

    private function getDescription()
    {
        if (isset($this['description'])) {
            return $this['description'];
        }

        $description = $this->description ?? 'no description';

        return "{$description} - " .
            Carbon::parse($this->created_at)->format('M d, Y');
    }

    private function getTime()
    {
        if (isset($this['time'])) {
            return $this['time'];
        }

        
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    private function getType()
    {
        if (isset($this['type'])) {
            return $this['type'];
        }

        return 'activity';
    }
}