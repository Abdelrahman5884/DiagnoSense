<?php

namespace App\Services;

use App\Models\AiAnalysisResult;
use App\Models\Doctor;
use App\Models\Visit;
use Carbon\Carbon;

class DashboardService
{
    public function getSummary(Doctor $doctor): array
    {
        $now = Carbon::now();
        $currentMonthStart  = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd   = $now->copy()->subMonth()->endOfMonth();

        $patientIds = $doctor->patients()->pluck('patients.id');

        $totalPatients = $patientIds->count();

        $todayVisits = Visit::where('doctor_id', $doctor->id)
            ->whereDate('next_visit_date', today())
            ->count();

        $reportsAnalyzed = AiAnalysisResult::whereIn('patient_id', $patientIds)
            ->where('status', 'completed')
            ->count();

        $patientsThisMonth = $doctor->patients()
            ->where('patients.created_at', '>=', $currentMonthStart)
            ->count();

        $patientsLastMonth = $doctor->patients()
            ->whereBetween('patients.created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $diff             = $patientsThisMonth - $patientsLastMonth;
        $growthPercentage = 0;

        if ($patientsLastMonth > 0) {
            $growthPercentage = round(($diff / $patientsLastMonth) * 100, 2);
        } elseif ($patientsThisMonth > 0) {
            $growthPercentage = 100;
        }

        return [
            'doctor_name'        => $doctor->user->name,
            'total_patients'     => $totalPatients,
            'today_appointments' => $todayVisits,
            'reports_analyzed'   => $reportsAnalyzed,
            'last_month_count'   => $patientsLastMonth,
            'this_month_count'   => $patientsThisMonth,
            'diff'               => $diff,
            'growth_percentage'  => $growthPercentage,
        ];
    }
}