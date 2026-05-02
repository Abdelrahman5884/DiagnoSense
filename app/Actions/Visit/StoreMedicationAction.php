<?php

namespace App\Actions\Visit;

use App\Models\Medication;
use App\Models\Visit;

class StoreMedicationAction extends StoreVisitRequirementAction
{
    public function execute(Visit $visit, array $data): Medication
    {
        $this->updateVisitIfNeeded($visit, $data);
        $medication = $visit->medications()->create([
            'name' => $data['name'],
            'dosage' => $data['dosage'] ?? null,
            'notes' => $data['notes'] ?? null,
            'visit_id' => $visit->id,
        ]);
        $medication['action'] = $data['action'];
        $medication->load('visit');

        return $medication;
    }
}
