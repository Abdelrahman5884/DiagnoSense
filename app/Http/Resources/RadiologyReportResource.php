<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RadiologyReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => $this->getRadiologyType(),
            'name' => $this->file_name,
            'doctor' => $this->getDoctorName(),
            'date' => $this->created_at->format('M d, Y'),
            'open_url' => Storage::url($this->file_path),
        ];
    }

    private function getRadiologyType()
    {
        if (str_contains(strtolower($this->file_name), 'mri')) {
            return 'MRI';
        }

        return 'X-Ray';
    }

    private function getDoctorName()
    {
        $doctor = $this->patient?->doctors()
            ->with('user')
            ->first();

        return $doctor?->user?->name
            ? 'Ref: Dr.'.$doctor->user->name
            : 'Ref: Dr.Unknown';
    }
}
