<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;

class MedicalFileService
{
    public function getPatientFiles(?Patient $patient, ?string $type = null, ?string $search = null): Collection
    {
        return $patient->reports()
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('file_name', 'like', "%{$search}%");
            })
            ->with(['patient.doctors.user'])
            ->latest()
            ->get();
    }
}
