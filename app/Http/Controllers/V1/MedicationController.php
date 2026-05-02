<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\DeleteMedicationRequest;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Resources\MedicationResource;
use App\Models\Medication;
use App\Models\Visit;
use App\Services\MedicationService;
use Illuminate\Http\JsonResponse;

class MedicationController
{
    public function __construct(
        protected MedicationService $medicationService
    ){}
    public function store(StoreMedicationRequest $request, Visit $visit) :JsonResponse
    {
        try{
            $data = $request->validated();
           $medication =  $this->medicationService->store($visit, $data);
           return ApiResponse::success(message:'Medication created successfully',data: new MedicationResource($medication));
        }catch (\Exception $e){
            \Log::error('Store Medication Error: '.$e->getMessage());
            return ApiResponse::error(message:'An error occurred while creating medication.',status:500);
        }
    }

    public function destroy(DeleteMedicationRequest $request, Medication $medication): JsonResponse
    {
        try{
            $this->medicationService->delete($medication);
            return ApiResponse::success(message:'Medication deleted successfully');
        }catch (\Exception $e){
            \Log::error('Delete Medication Error: '.$e->getMessage());
            return ApiResponse::error(message:'An error occurred while deleting medication.',status:500);
        }
    }
}
