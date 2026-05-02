<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\GetNextVisitDetailsRequest;
use App\Http\Requests\StoreNextVisitRequest;
use App\Http\Resources\MedicationResource;
use App\Http\Resources\NextVisitResource;
use App\Http\Resources\TaskResource;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class VisitController extends Controller
{
    public function __construct(
        protected  VisitService $visitService
    ){}

    public function show(GetNextVisitDetailsRequest $request, Visit $visit) :JsonResponse
    {
        try{
            $visitDetails = $this->visitService->getVisitDetails($visit);
            $data = [
                'task' => TaskResource::collection($visitDetails->tasks),
                'medications' => MedicationResource::collection($visitDetails->medications),
                'next_visit_date' => $visit->next_visit_date ?
                    Carbon::parse($visit->next_visit_date)->timezone('Africa/Cairo')->format('D, F j, Y - g:i A') : null
            ];
            return ApiResponse::success(message: 'Visit details retrieved successfully.', data: $data);
        }catch (\Exception $e) {
            \Log::error('Show Visit Error: '.$e->getMessage());
            return ApiResponse::error(message:'An error occurred while fetching visit details.',status:500);
        }
    }
    public function store(StoreNextVisitRequest $request, Patient $patient): JsonResponse
    {
        try{
            $data = $request->validated();
            $doctor = auth()->user()->doctor;
            $nextVisit = $this->visitService->store($data, $patient, $doctor);
            return ApiResponse::success(message: 'Visit created successfully.', data: new NextVisitResource($nextVisit),);
        }catch (\Exception $e) {
            \Log::error('Store Visit Error: '.$e->getMessage());

            return ApiResponse::error(message:'An error occurred while creating visit.',status:500);
        }
    }
}
