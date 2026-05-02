<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;

class VisitService
{
    public function getVisitDetails(Visit $visit) : Visit
    {
        $visitDetails = $visit->load('tasks', 'medications');
        return $visitDetails;
    }

    public  function store(array $data, Patient $patient, Doctor $doctor) : Visit
    {
        $status = $data['action'] == 'save' ? 'completed' : 'draft';
        $nextVisitDate = $data['next_visit_date'] ?? null;
        $visit = $doctor->visits()->create([
            'patient_id' => $patient->id,
            'next_visit_date' => $nextVisitDate,
            'status' => $status
        ]);
        return $visit;
    }
}
