<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function getPaginatedPatients(int $doctorId, array $params): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['users.id', 'users.name'])
            ->join('patients', 'patients.user_id', '=', 'users.id')
            ->join('doctor_patient', 'doctor_patient.patient_id', '=', 'patients.id')
            ->where('doctor_patient.doctor_id', $doctorId);

        $query->when(! empty($params['search']), function ($q) use ($params) {
            $term = $params['search'];
            $q->where(function ($sub) use ($term) {
                if (is_numeric($term)) {
                    $sub->where('patients.notional_id', 'LIKE', $term.'%');
                } else {
                    $sub->where('users.name', 'LIKE', $term.'%');
                }
            });
        });

        $query->when(! empty($params['status']), function ($q) use ($params) {
            $q->where('patients.status', $params['status']);
        });

        return $query->with([
            'patient:id,user_id,date_of_birth,status,created_at,notional_id',
            'patient.latestAiAnalysisResult:id,patient_id,ai_insight',
            'patient.latestVisit',
        ])
            ->latest('users.created_at')
            ->paginate(12)
            ->appends($params);
    }

    public function getPatientOverview(Doctor $doctor, int $patientId): ?Patient
    {
        return $doctor->patients()->with([
            'user',
            'medicalHistory',
            'latestAiAnalysisResult',
        ])->find($patientId);
    }

    public function deletePatient(Doctor $doctor, int $patientId): bool
    {
        return DB::transaction(function () use ($doctor, $patientId) {
            $patient = $doctor->patients()->find($patientId);

            if (! $patient) {
                return false;
            }

            return (bool) $patient->delete();
        });
    }
}
