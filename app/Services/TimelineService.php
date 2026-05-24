<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;

class TimelineService
{
    public function getPatientTimeline(Patient $patient): Collection
    {
        $visits = $patient->visits()
            ->with('doctor.user')
            ->latest()
            ->get();

        $tasks = $patient->tasks()
            ->with(['visit.doctor.user', 'doctor.user'])
            ->latest()
            ->get();

        return $visits->concat($tasks)->sortByDesc('created_at')->values();
    }
}
