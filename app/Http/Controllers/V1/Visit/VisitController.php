<?php

namespace App\Http\Controllers\V1\Visit;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Visit\AttendVisitRequest;
use App\Http\Requests\Visit\GetNextVisitDetailsRequest;
use App\Http\Requests\Visit\GetVisitRequest;
use App\Http\Requests\Visit\StoreNextVisitRequest;
use App\Http\Requests\Visit\UpdateVisitRequest;
use App\Http\Resources\Medication\MedicationResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\Visit\NextVisitResource;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService
    ) {}

    public function index(GetNextVisitDetailsRequest $request, Patient $patient): JsonResponse
    {
        try {
            $visitDetails = $this->visitService->getVisitDetails($patient);
            $nextVisit = $visitDetails
                ->filter(fn (Visit $visit) => $visit->next_visit_date
                    && $visit->next_visit_date->greaterThanOrEqualTo(now())
                    && $visit->status !== 'attended'
                )
                ->sortBy('next_visit_date')
                ->first();
            $data = [
                'tasks' => TaskResource::collection($visitDetails->flatMap->tasks),
                'medications' => MedicationResource::collection($visitDetails->flatMap->medications),
                'next_visit_id' => $nextVisit?->id,
                'next_visit_date' =>$nextVisit?->next_visit_date
                    ? $nextVisit->next_visit_date->format('D, M j, Y g:i A')
                    : null,
            ];

            return ApiResponse::success(message: 'Visit details retrieved successfully.', data: $data);
        } catch (\Exception $e) {
            \Log::error('Show Visit Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while fetching visit details.', status: 500);
        }
    }

    public function store(StoreNextVisitRequest $request, Patient $patient): JsonResponse
    {
        try {
            $data = $request->validated();
            $doctor = auth()->user()->doctor;
            $nextVisit = $this->visitService->store($data, $patient, $doctor);

            return ApiResponse::success(message: 'Visit created successfully.', data: new NextVisitResource($nextVisit));
        } catch (\Exception $e) {
            \Log::error('Store Visit Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while creating visit.', status: 500);
        }
    }

    public function show(): JsonResponse
    {
        try {
            $patient = auth()->user()->patient;
            $nextVisit = $this->visitService->getNextVisit($patient);
            if (! $nextVisit) {
                return ApiResponse::success(message: 'No upcoming visit.', status: 200);
            }

            return ApiResponse::success(
                message: 'Next visit retrieved successfully.',
                data: new NextVisitResource($nextVisit)
            );
        } catch (\Exception $e) {
            \Log::error('Show nest visit Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while fetching next visit.', status: 500);
        }
    }

    public function edit(GetVisitRequest $request, Visit $visit): JsonResponse
    {
        try{
            return ApiResponse::success(message: 'Next Visit date  retrieved successfully.', data:[
                'next_visit_date' => $visit->next_visit_date->format('Y-m-d H:i:s'),
            ]);
        }catch (\Exception $e) {
            \Log::error('Edit Visit Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while fetching visit details.', status: 500);
        }
    }

    public function update(UpdateVisitRequest $request, Visit $visit): JsonResponse
    {
        try {
            $this->visitService->updateNextVisit($visit, $request->validated());
            return ApiResponse::success(message: 'Visit updated successfully.');
        }catch (\Exception $e) {
            \Log::error('Update Visit Error: '.$e->getMessage());
            return ApiResponse::error(message: 'An error occurred while updating visit.', status: 500);
        }
    }
    public function attend(AttendVisitRequest $request, Visit $visit): JsonResponse
    {
        try {
            $this->visitService->attend($visit);

            return ApiResponse::success(message: 'Visit attended successfully.');
        } catch (\Exception $e) {
            \Log::error('Attend Visit Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while attending visit.', status: 500);
        }
    }
}
