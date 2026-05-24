<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class TopDiseaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => (string) $this['label'],
            'value' => (int) $this['value'],
        ];
    }

    public static function collection($resource): AnonymousResourceCollection
    {
        $formatted = collect($resource)->map(fn ($count, $name) => [
            'label' => $name,
            'value' => $count,
        ])->values()->all();

        return parent::collection($formatted);
    }
}
