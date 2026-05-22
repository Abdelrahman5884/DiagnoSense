<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\MedicalHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\AiAnalysisResult;
use App\Models\Visit;
use Carbon\Carbon;

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

 public function getSummary(Doctor $doctor): array
    {
        $doctor->loadMissing('user');

        $dates = $this->getDateRanges();

        $patients = $this->getPatients($doctor);

        $patientStats = $this->getPatientStats(
            $patients,
            $dates['currentMonthStart'],
            $dates['previousMonthStart'],
            $dates['previousMonthEnd']
        );

        $reportsAnalyzed = $this->getReportsAnalyzed($patients);

        $todayVisits = $this->getTodayVisits($doctor);

        $diff = $patientStats['this_month'] - $patientStats['last_month'];

        $growthPercentage = $this->calculateGrowthPercentage(
            $patientStats['this_month'],
            $patientStats['last_month'],
        );

        return [
            'doctor_name' => $doctor->user->name,
            'total_patients' => $patientStats['total'],
            'today_appointments' => $todayVisits,
            'reports_analyzed' => $reportsAnalyzed,
            'last_month_count' => $patientStats['last_month'],
            'this_month_count' => $patientStats['this_month'],
            'diff' => $diff,
            'growth_percentage' => $growthPercentage,
        ];
    }

    private function getDateRanges(): array
    {
        $now = Carbon::now();

        return [
            'currentMonthStart' => $now->copy()->startOfMonth(),
            'previousMonthStart' => $now->copy()->subMonth()->startOfMonth(),
            'previousMonthEnd' => $now->copy()->subMonth()->endOfMonth(),
        ];
    }

    private function getPatients(Doctor $doctor): Collection
    {
        return $doctor->patients()
            ->select('patients.id', 'patients.created_at')
            ->get();
    }

    private function getTodayVisits(Doctor $doctor): int
    {
        return Visit::where('doctor_id', $doctor->id)
            ->whereDate('next_visit_date', today())
            ->count();
    }

    private function getReportsAnalyzed(Collection $patients): int
    {
        return AiAnalysisResult::whereIn(
            'patient_id',
            $patients->pluck('id')
        )
            ->where('status', 'completed')
            ->count();
    }

    private function getPatientStats(Collection $patients, Carbon $currentMonthStart, Carbon $previousMonthStart, Carbon $previousMonthEnd
    ): array {

        return [
            'total' => $patients->count(),

            'this_month' => $patients
                ->where('created_at', '>=', $currentMonthStart)
                ->count(),

            'last_month' => $patients
                ->whereBetween(
                    'created_at',
                    [$previousMonthStart, $previousMonthEnd]
                )
                ->count(),
        ];
    }

    private function calculateGrowthPercentage(int $patientsThisMonth, int $patientsLastMonth): float
    {

        $diff = $patientsThisMonth - $patientsLastMonth;

        if ($patientsLastMonth > 0) {
            return round(($diff / $patientsLastMonth) * 100, 2);
        }

        return $patientsThisMonth > 0 ? 100 : 0;
    }
}
