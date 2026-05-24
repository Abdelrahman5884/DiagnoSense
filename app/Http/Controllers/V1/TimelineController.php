<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\TimelineResource;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct(
        protected TimelineService $timelineService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $patient = $request->user()->patient;
            if (! $patient) {
                return ApiResponse::error(message: 'No patient profile found for the user.', status: 404);
            }
            $timeline = $this->timelineService->getPatientTimeline($patient);

            return ApiResponse::success(
                message: 'Timeline retrieved successfully',
                data: TimelineResource::collection($timeline)
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching patient timeline: '.$e->getMessage());

            return ApiResponse::error(message: __('Failed to fetch timeline'), status: 500);
        }
    }
}
