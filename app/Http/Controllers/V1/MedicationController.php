<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\DeleteMedicationRequest;
use App\Http\Requests\NextVisit\StoreMedicationRequest;
use App\Http\Resources\MedicationListResource;
use App\Http\Resources\MedicationResource;
use App\Models\Medication;
use App\Models\Visit;
use App\Services\MedicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController
{
    public function __construct(
        protected MedicationService $medicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $patient = $request->user()->patient;
            if (! $patient) {
                return ApiResponse::error(message: 'No patient profile found for the user.', status: 404);
            }
            $medications = $this->medicationService->getPatientMedications($patient);

            return ApiResponse::success(
                message: 'Patient medications retrieved successfully',
                data: MedicationListResource::collection($medications)
            );
        } catch (\Exception $e) {
            \Log::error('Get Medications Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while retrieving medications.', status: 500);
        }
    }

    public function store(StoreMedicationRequest $request, Visit $visit): JsonResponse
    {
        try {
            $data = $request->validated();
            $medication = $this->medicationService->store($visit, $data);

            return ApiResponse::success(message: 'Medication created successfully', data: new MedicationResource($medication));
        } catch (\Exception $e) {
            \Log::error('Store Medication Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while creating medication.', status: 500);
        }
    }

    public function destroy(DeleteMedicationRequest $request, Medication $medication): JsonResponse
    {
        try {
            $this->medicationService->delete($medication);

            return ApiResponse::success(message: 'Medication deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Delete Medication Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while deleting medication.', status: 500);
        }
    }
}
