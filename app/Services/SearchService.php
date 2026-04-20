<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function search(int $doctorId, ?string $term): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['users.id', 'users.name'])
            ->join('patients', 'patients.user_id', '=', 'users.id')
            ->join('doctor_patient', 'doctor_patient.patient_id', '=', 'patients.id')
            ->where('doctor_patient.doctor_id', $doctorId);

        $query->where(function ($q) use ($term) {
            if (is_numeric($term)) {
                $q->where('patients.notional_id', 'LIKE', $term.'%');
            } else {
                $q->where('users.name', 'LIKE', $term.'%');
            }
        });

        return $query->with([
            'patient:id,user_id,date_of_birth,status,created_at,notional_id',
            'patient.latestAiAnalysisResult:id,patient_id,ai_insight',
            'patient.latestVisit',
        ])
            ->paginate(12)
            ->appends(['search' => $term]);
    }
}
