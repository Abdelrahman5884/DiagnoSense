<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\DeletePatientRequest;
use App\Http\Requests\PatientListRequest;
use App\Http\Requests\PatientOverviewRequest;
use App\Http\Resources\PatientOverviewResource;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
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

            return ApiResponse::error(message: 'An error occurred while fetching patients.', status: 500);
        }
    }

    public function overview(PatientOverviewRequest $request, Patient $patient): JsonResponse
    {

        try {

            $patient = $this->patientService
                ->getPatientOverview($patient);

            return ApiResponse::success(
                message: 'Patient retrieved successfully.',
                data: new PatientOverviewResource($patient)
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching patient overview: '.$e->getMessage(), ['id' => $patient->id]);

            return ApiResponse::error(
                message: 'Failed to retrieve patient data.',
                status: 500
            );
        }
    }

    public function destroy(DeletePatientRequest $request, Patient $patient): JsonResponse
    {

        try {

            $this->patientService->deletePatient($patient);

            return ApiResponse::success(
                message: 'Patient deleted successfully.'
            );

        } catch (\Exception $e) {
            \Log::error('Error deleting patient: '.$e->getMessage(), ['id' => $patient->id]);

            return ApiResponse::error(
                message: 'Failed to delete patient, please try again later.',
                status: 500
            );
        }
    }
}
