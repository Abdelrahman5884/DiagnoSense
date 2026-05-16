<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\Patient\PatientListRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
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

            return ApiResponse::error(message: 'An error occurred while creating patient.', status: 500);
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
                status: 500
            );
        }
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        try {
            $this->patientService->update($patient, $request->validated());

            return ApiResponse::success(message: 'Patient file updated successfully');
        } catch (\Exception $e) {
            \Log::error('Update Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Update failed: '.$e->getMessage(), status: 500);
        }
    }

    public function triggerAiAnalysis(Patient $patient): JsonResponse
    {
        try {
            $analysis = $this->patientService->runAiAnalysis($patient, [], true);

            return ApiResponse::success(message: 'AI Is Processing Now Due To Upgrade', data: [
                'analysis_id' => $analysis->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('AI Analysis Trigger Error: '.$e->getMessage());

            return ApiResponse::error(message: 'AI Analysis Trigger failed: '.$e->getMessage(), status: 500);
        }
    }
}
