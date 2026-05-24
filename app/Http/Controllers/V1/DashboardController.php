<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\CurrentVisitResource;
use App\Http\Resources\VisitsQueueResource;
use App\Http\Resources\DashboardStatusResource;
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
