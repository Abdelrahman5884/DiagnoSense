<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\CurrentVisitDashboardResource;
use App\Http\Resources\DashboardStatusResource;
use App\Http\Resources\QueueDashboardResource;
use App\Http\Resources\TopDiseaseResource;
use App\Http\Resources\WidgetsDashboardResource;
use App\Models\AiAnalysisResult;
use App\Models\Patient;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function summary(Request $request)
    {
        $doctor = $request->user()->doctor;
        if (! $doctor) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

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

    public function statusDistribution(Request $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            if (! $doctor) {
                return ApiResponse::error(message: 'Doctor not found', status: 404);
            }
            $distribution = $this->dashboardService->getPatientStatusDistribution($doctor);

            return ApiResponse::success(
                message: 'Status distribution retrieved successfully',
                data: new DashboardStatusResource($distribution)
            );
        } catch (\Exception $e) {
            \Log::error('Error retrieving status distribution: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to retrieve status distribution', status: 500);
        }
    }

    public function topDiseases(Request $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            if (! $doctor) {
                return ApiResponse::error(message: 'Doctor not found', status: 404);
            }
            $topDiseases = $this->dashboardService->getTopChronicDiseases($doctor);

            return ApiResponse::success(
                message: 'Top 5 chronic diseases retrieved successfully',
                data: TopDiseaseResource::collection($topDiseases)
            );
        } catch (\Exception $e) {
            \Log::error('Error retrieving top diseases: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to retrieve top diseases', status: 500);
        }
    }

    public function todayVisits(Request $request)
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $patientsToday = Patient::whereHas('doctors', function ($q) use ($doctor) {
            $q->where('doctors.id', $doctor->id);
        })
            ->whereDate('patients.next_visit_date', today())
            ->with('latestAiAnalysisResult')
            ->orderBy('patients.next_visit_date', 'asc')
            ->take(5)
            ->get();
        $currentPatient = $patientsToday->first();
        $totalTodayCount = Patient::whereHas('doctors', fn ($q) => $q->where('doctors.id', $doctor->id))
            ->whereDate('patients.next_visit_date', today())
            ->count();
        $request->merge(['current_id' => $currentPatient?->id]);

        return ApiResponse::success(
            'Queue retrieved successfully',
            [
                'current_attending' => $currentPatient ? new CurrentVisitDashboardResource($currentPatient) : null,
                'full_queue_list' => QueueDashboardResource::collection($patientsToday),
                'remaining_count_label' => ($totalTodayCount > 1 ? ($totalTodayCount - 1) : 0).' remaining',
            ],
            200
        );

    }

    public function markAttended(Request $request, $patientId)
    {
        $doctor = $request->user()->doctor;
        $patient = $doctor->patients()->findorfail($patientId);
        if ($patient->next_visit_date && Carbon::parse($patient->next_visit_date)->isToday()) {
            $patient->update([
                'next_visit_date' => null,
            ]);
        }

        return ApiResponse::success('Patient marked as attended and queue updated successfully.', null, 200);
    }
}
