<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LabReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->file_name,
            'doctor' => $this->getDoctorName(),
            'date' => $this->created_at->format('M d, Y'),
            'view_url' => Storage::url($this->file_path),
        ];
    }

    private function getDoctorName()
    {
        $doctor = $this->patient?->doctors()
            ->with('user')
            ->first();

        return $doctor?->user?->name
            ? 'Dr.'.$doctor->user->name
            : 'Dr.Unknown';
    }
}
