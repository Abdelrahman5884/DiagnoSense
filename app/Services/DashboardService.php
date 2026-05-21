<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getPatientStatusDistribution(Doctor $doctor): Collection
    {
        return $doctor->patients()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function getTopChronicDiseases(Doctor $doctor): Collection
    {
        $histories = MedicalHistory::whereHas('patient.doctors', function ($query) use ($doctor) {
            $query->where('doctors.id', $doctor->id);
        })
            ->whereNotNull('chronic_diseases')
            ->pluck('chronic_diseases');

        return collect($histories)
            ->flatMap(function ($diseases) {
                return is_string($diseases) ? json_decode($diseases, true) : (array) $diseases;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5);
    }
}
