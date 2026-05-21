<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\NextVisit\GetNextVisitDetailsRequest;
use App\Http\Requests\NextVisit\StoreNextVisitRequest;
use App\Http\Resources\MedicationResource;
use App\Http\Resources\NextVisitResource;
use App\Http\Resources\TaskResource;
use App\Models\Patient;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class VisitController extends Controller
{
    public function __construct(
        protected VisitService $visitService
    ) {}

    public function index(GetNextVisitDetailsRequest $request, Patient $patient): JsonResponse
    {
        try {
            $visitDetails = $this->visitService->getVisitDetails($patient);
            $data = [
                'tasks' => TaskResource::collection($visitDetails->flatMap->tasks),
                'medications' => MedicationResource::collection($visitDetails->flatMap->medications),
                'next_visit_date' => $visitDetails->first()?->next_visit_date ? Carbon::parse($visitDetails->first()->next_visit_date)->toDateString() : null,
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
}
