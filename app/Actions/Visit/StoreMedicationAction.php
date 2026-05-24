<?php

namespace App\Actions\Visit;

use App\Helpers\PushNotification;
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
            'frequency' => $data['frequency'] ?? null,
            'duration' => $data['duration'] ?? null,
            'visit_id' => $visit->id,
        ]);
        $medication['action'] = $data['action'];
        $medication->load('visit');
        
        $patient = $visit->patient;
        $doctorName = $visit->doctor->user->name;
        PushNotification::sendToPatient(
        patient: $patient,
        type: 'medication',
        title: __('New Medication Added'),
        body: __('Dr. :name added a new medication: :med', ['name' => $doctorName, 'med' => $medication->name])
        );

        return $medication;
    }
}
