<?php

namespace App\Services;

use App\Actions\Visit\StoreMedicationAction;
use App\Models\Medication;
use App\Models\Visit;

class MedicationService
{
    public function store(Visit $visit, array $data) : Medication
    {
        $doctor = auth()->user()->doctor;
        return (new StoreMedicationAction)->execute($visit, $data, $doctor);
    }

    public function delete(Medication $medication) : void
    {
        $medication->delete();
    }
}
