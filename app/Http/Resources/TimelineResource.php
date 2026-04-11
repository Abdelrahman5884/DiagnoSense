<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => strtoupper($this['type']),
            'title' => $this['title'],
            'description' => $this['description'],
            'doctor' => 'Dr.'.$this['doctor'],
            'date' => Carbon::parse($this['date'])->format('d'),
            'month' => Carbon::parse($this['date'])->format('M'),
            'year' => Carbon::parse($this['date'])->format('Y'),
        ];
    }
}
