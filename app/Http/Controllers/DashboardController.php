<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\AiAnalysisResult;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

   public function summary(Request $request)
   {
        $doctor = $request->user()->doctor;
        if (!$doctor) {
             return ApiResponse::error('Unauthorized',null,403);
        }

        $activePatients = $doctor->patients()->count();
        $todayVisits = Patient::whereHas('doctors', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->whereDate('next_visit_date', today())
        ->count();

        $reportsAnalyzed = AiAnalysisResult::whereHas('patient', function ($query) use ($doctor) {
            $query->whereHas('doctors', function ($q) use ($doctor) {
                $q->where('doctor_id', $doctor->id);
            });
            })->where('status', 'completed')
              ->count();

        $currentMonthPatients = Patient::whereHas('doctors', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->whereBetween('created_at', [now()->startOfMonth(),now()->endOfMonth()
        ])->count();

        $lastMonthPatients = Patient::whereHas('doctors', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();

        if ($lastMonthPatients > 0) {
           $percentageChange = (($currentMonthPatients - $lastMonthPatients) / $lastMonthPatients) * 100;
        } else {$percentageChange = $currentMonthPatients > 0 ? 100 : 0;
        }

        return ApiResponse::success(
          'Dashboard summary retrieved successfully',
          [
            "name" => $request->user()->name,
            'active_patients' => $activePatients,
            'today_visits' => $todayVisits,
            'reports_analyzed' => $reportsAnalyzed,
            'patients_this_month' => $currentMonthPatients,
            'patients_last_month' => $lastMonthPatients,
            'patients_percentage' => round($percentageChange, 2)
        ],200
        );
    }

   public function statusDistribution(Request $request)
  {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $patientIds = $doctor->patients()->pluck('patients.id');

        $distribution = Patient::whereIn('id', $patientIds)
           ->select('status', DB::raw('count(*) as total'))
           ->groupBy('status')
           ->pluck('total', 'status');

        $statuses = ['critical', 'stable','under review'];

        $result = collect($statuses)->map(function ($status) use ($distribution) {
           return [
            'status' => $status,
            'total' => $distribution[$status] ?? 0
             ];})->values();

        return ApiResponse::success(
           'Status distribution retrieved successfully',
           $result,
           200
    );
    }

   public function topDiseases(Request $request)
   {
    $doctor = $request->user()->doctor;
    if (!$doctor) {
        return ApiResponse::error('Unauthorized', null, 403);
    }

    $patients = $doctor->patients()->pluck('patients.id');
    $histories = MedicalHistory::whereIn('patient_id', $patients)
        ->pluck('chronic_diseases');

    $diseases = [];

    foreach ($histories as $history) {

        if (!$history) {
            continue;
        }

        foreach ($history as $disease) {

            if (!isset($diseases[$disease])) {
                $diseases[$disease] = 0;
            }
            $diseases[$disease]++;
        }
    }

    arsort($diseases);
    $topDiseases = collect($diseases)
        ->take(5)
        ->map(function ($count, $name) {

            return [
                'disease' => $name,
                'total' => $count
            ];
        })->values();

    return ApiResponse::success(
        'Top diseases retrieved successfully',
        $topDiseases,
        200
    );
    }

    public function todayVisits(Request $request)
    {
    $doctor = $request->user()->doctor;

    if (!$doctor) {
        return ApiResponse::error('Unauthorized', null, 403);
    }

    $patientIds = $doctor->patients()->pluck('patients.id');

    $patients = Patient::whereIn('id', $patientIds)
        ->whereNotNull('next_visit_date')
        ->whereDate('next_visit_date', now()->toDateString())
        ->with('user')
        ->orderBy('next_visit_date')
        ->get()
        ->map(function ($patient) {
            return [
                'patient_id' => $patient->id,
                'name' => $patient->user->name ?? null,
                'status' => $patient->status,
                'visit_date' => $patient->next_visit_date
            ];
        });

    return ApiResponse::success(
        'Today visits retrieved successfully',
        $patients,
        200
    );
    }
}