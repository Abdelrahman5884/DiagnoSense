<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\Patient\PatientListRequest;
use App\Http\Requests\Patient\StorePatientRequest;
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

            return ApiResponse::error(message: 'An error occurred while fetching patients.', data: null, status: 500);
        }
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->patientService->store($data);

            return ApiResponse::success(
                message: 'Patient created successfully and AI analysis is in progress.',
                data : [
                    'patient_id' => $result['patient']->id,
                    'analysis_result_id' => $result['analysisResult']->id,
                ],
                status: 201
            );
        } catch (\Exception $e) {
            \Log::error('Patient Store Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while creating patient.', data: null, status: 500);
        }
    }

    public function getKeyInfo(Patient $patient): JsonResponse
    {
        try {
            $result = $this->patientService->getPatientKeyInfo($patient);

            return ApiResponse::success(
                message: $result['message'],
                data: $result['data'],
            );
        } catch (\Exception $e) {
            \Log::error("Error retrieving key info for Patient {$patient->id}: ".$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while fetching key information.',
                data: null,
                status: 500
            );
        }
    }

    public function getDecisionSupport(Patient $patient): JsonResponse
    {
        try {
            $result = $this->patientService->getPatientDecisionSupport($patient);

            return ApiResponse::success(
                message: $result['message'],
                data: $result['data']
            );
        } catch (\Exception $e) {
            \Log::error("Decision Support Error for Patient {$patient->id}: ".$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while fetching decision support information.',
                data: null,
                status: 500
            );
        }
    }

    public function getComparativeAnalysis(Patient $patient): JsonResponse
    {
        try {
            $result = $this->patientService->getPatientComparativeAnalysis($patient);
            if (empty($result)) {
                return ApiResponse::success(
                    message: 'No comparative analysis data available for this patient.',
                    data: null
                );
            }

            return ApiResponse::success(
                message: $result['message'],
                data: $result['data']
            );

        } catch (\Exception $e) {
            \Log::error("Comparative Analysis Error for Patient {$patient->id}: ".$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while fetching comparative analysis.',
                data: null,
                status: 500
            );
        }
    }
}
