<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'age' => $this->age,
                'gender' => $this->gender,
                'national_id' => $this->national_id,
            ],
            'medical_history' => new MedicalHistoryResource($this->medicalHistory),
            'existing_files' => $this->reports->map(fn($report) => [
                'id' => $report->id,
                'type' => $report->type,
                'name' => $report->file_name,
                'url' => Storage::disk('azure')->temporaryUrl($report->file_path, now()->addMinutes(30)),
            ]),
        ];
    }
}
