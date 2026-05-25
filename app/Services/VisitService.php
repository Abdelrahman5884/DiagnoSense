<?php

namespace App\Services;

use App\Helpers\PushNotification;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Support\Collection;

class VisitService
{
    public function getVisitDetails(Patient $patient): Collection
    {
        return Visit::query()
            ->whereBelongsTo($patient)
            ->with(['tasks', 'medications'])
            ->latest()
            ->get();
    }

    public function getNextVisit(Patient $patient): ?Visit
    {
        $nextVisit = $patient->visits()
            ->where('next_visit_date', '>=', now())
            ->where('status', '!=', 'attended')
            ->with('doctor.user')
            ->orderBy('next_visit_date')
            ->first();

        return $nextVisit;
    }

    public function store(array $data, Patient $patient, Doctor $doctor): Visit
    {
        $status = $data['action'] == 'save' ? 'completed' : 'draft';
        $nextVisitDate = $data['next_visit_date'] ?? null;
        $visit = $doctor->visits()->create([
            'patient_id' => $patient->id,
            'next_visit_date' => $nextVisitDate,
            'status' => $status,
        ]);

        PushNotification::sendToPatient(
        patient: $patient,
        type: 'visit',
        title: __('Upcoming Appointment Scheduled'),
        body: __('Your next visit is scheduled on: :date', ['date' => $visit->next_visit_date->format('Y-m-d h:i A')])
        );

        return $visit->load('doctor.user');
    }

    public function attend(Visit $visit): void
    {
        $visit->update(['status' => 'attended']);
    }
}
