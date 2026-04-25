<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\V1\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\PatientListRequest;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientOverviewResource;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function __construct(
        protected PatientService $patientService
    ) {}

    public function index(PatientListRequest $request): JsonResponse
    {
        try {
            $doctorId = auth()->user()->doctor->id;
            $patients = $this->patientService->getPaginatedPatients($doctorId, $request->validated());

            return ApiResponse::success(
                message: 'Patients list retrieved successfully',
                data: PatientResource::collection($patients)->response()->getData(true),
            );

        } catch (\Exception $e) {
            \Log::error('Patient Index Error: '.$e->getMessage());

            return ApiResponse::error(message:'An error occurred while fetching patients.',status:500);
        }
    }
    public function overview(int $patientId): JsonResponse
    {
        try {
            $doctor = auth()->user()->doctor;
            $patient = $this->patientService->getPatientOverview($doctor, $patientId);

            if (! $patient) {
                return ApiResponse::error(
                    message: 'Unauthorized or patient not found in your list',
                    status: 403
                );
            }

            return ApiResponse::success(
                message: 'Patient retrieved successfully.',
                data: new PatientOverviewResource($patient)
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching patient overview: ' . $e->getMessage(), ['id' => $patientId]);

            return ApiResponse::error(
                message: 'Failed to retrieve patient data.',
                status: 500
            );
        }
    }

    public function destroy(int $patientId): JsonResponse
    {
        try {
            $doctor = auth()->user()->doctor;
            $result = $this->patientService->deletePatient($doctor, $patientId);

            if (! $result) {
                return ApiResponse::error(
                    message: 'Patient not found or could not be deleted.',
                    status: 404
                );
            }
            return ApiResponse::success(
                message: 'Patient deleted successfully.'
            );

        } catch (\Exception $e) {
            \Log::error('Error deleting patient: ' . $e->getMessage(), ['id' => $patientId]);

            return ApiResponse::error(
                message: 'Failed to delete patient, please try again later.',
                status: 500
            );
        }
    }
}
