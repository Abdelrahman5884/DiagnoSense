<?php

namespace App\Http\Resources\Patient;

use App\Helpers\FileSystem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientEditResource extends JsonResource
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
            'personal_info' => [
                'name' => $this->user->name,
                'contact' => $this->user->contact,
                'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
                'gender' => $this->gender,
                'national_id' => $this->national_id,
            ],
            'medical_history' => new MedicalHistoryResource($this->medicalHistory),
            'existing_files' => $this->reports->map(fn ($report) => [
                'id' => $report->id,
                'type' => $report->type,
                'name' => $report->file_name,
                'url' => FileSystem::getTempUrl($report->file_path),
            ]),
        ];
    }
}
