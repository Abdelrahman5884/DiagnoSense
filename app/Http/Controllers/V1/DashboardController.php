<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\CurrentVisitResource;
use App\Http\Resources\VisitsQueueResource;
use App\Http\Resources\WidgetsDashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;

            $stats = $this->dashboardService->getSummary($doctor);

            return ApiResponse::success(
                message: 'Dashboard summary retrieved successfully',
                data: new WidgetsDashboardResource($stats),
            );
        } catch (\Exception $e) {
            \Log::error('Error retrieving dashboard summary: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to retrieve dashboard summary, please try again later.', status: 500);
        }
    }

    public function statusDistribution(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $distribution = $doctor->patients()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalPatients = $distribution->sum();

        $statuses = ['critical', 'stable', 'under review'];

        $result = collect($statuses)->map(function ($status) use ($distribution, $totalPatients) {
            $count = $distribution[$status] ?? 0;

            return [
                'status' => $status,
                'value' => $count,
                'percentage' => $totalPatients > 0 ? round(($count / $totalPatients) * 100) : 0,
            ];
        })->values();

        return ApiResponse::success(
            'Status distribution retrieved successfully',
            [
                'total_registered_patients' => $totalPatients,
                'pie_chart_data' => $result,
            ],
            200
        );
    }

    public function topDiseases(Request $request)
    {
        $doctor = $request->user()->doctor;
        if (! $doctor) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $patientIds = $doctor->patients()->pluck('patients.id');
        $histories = MedicalHistory::whereIn('patient_id', $patientIds)
            ->whereNotNull('chronic_diseases')
            ->pluck('chronic_diseases');

        $topDiseases = collect($histories)
            ->flatMap(fn ($diseases) => (array) $diseases)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $name) => [
                'label' => $name,
                'value' => $count,
            ])
            ->values();

        return ApiResponse::success(
            'Top 5 chronic diseases retrieved successfully',
            $topDiseases,
            200
        );

    }

    public function todayVisits(): JsonResponse
    {
        try {
            $doctor = auth()->user()->doctor;
            $result = $this->dashboardService->getTodayVisit($doctor);
            request()->merge(['current_patient_id' => $result['currentPatient']?->patient->id ?? null]);

            return ApiResponse::success(
                message: 'Queue retrieved successfully',
                data: [
                    'current_attending' => $result['currentPatient'] ? new CurrentVisitResource($result['currentPatient']) : null,
                    'full_queue_list' => $result['todayPatients']->isNotEmpty() ? VisitsQueueResource::collection($result['todayPatients']) : null,
                    'remaining_count_label' => ($result['totalTodayCount'] > 1 ? ($result['totalTodayCount'] - 1) : 0).' remaining',
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Error retrieving today\'s visits: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to retrieve today\'s visits, please try again later.', status: 500);
        }
    }
}
