<?php

namespace App\Services;

use App\Actions\Visit\StoreMedicationAction;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Collection;

class MedicationService
{
    public function getPatientMedications(Patient $patient): Collection
    {
        return $patient->medications()
            ->latest()
            ->get();
    }
    public function store(Visit $visit, array $data): Medication
    {
        return (new StoreMedicationAction)->execute($visit, $data);
    }

    public function delete(Medication $medication): void
    {
        $medication->delete();
    }
}
