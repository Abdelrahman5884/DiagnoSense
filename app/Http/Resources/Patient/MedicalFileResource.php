<?php

namespace App\Http\Resources\Patient;

use App\Helpers\FileSystem;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MedicalFileResource extends JsonResource
{
    public function toArray($request): array
    {
        $doctor = $this->patient?->doctors->first();
        $doctorName = $doctor?->user?->name ? 'Dr. '.$doctor->user->name : 'Unknown';

        return [
            'id' => $this->id,
            'name' => $this->file_name,
            'referred_by' => 'Ref: '.$doctorName,
            'date' => $this->created_at ? $this->created_at->format('M d, Y') : null,
            'extension' => Str::upper(pathinfo($this->file_name, PATHINFO_EXTENSION)),
            'file_url' => $this->file_path ? FileSystem::getTempUrl($this->file_path) : null,
        ];
    }
}
