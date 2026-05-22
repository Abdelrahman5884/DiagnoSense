<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\Patient\PatientListRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Requests\UpdatePatientStatusRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\PatientEditResource;
use App\Http\Resources\PatientOverviewResource;
use App\Http\Resources\PatientResource;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            \Log::error('Error fetching patient overview: '.$e->getMessage(), ['id' => $patientId]);

            return ApiResponse::error(
                message: 'Failed to retrieve patient data.',
                status: 500
            );
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

    public function destroy(int $patientId): JsonResponse
    {
        try {
            $doctor = auth()->user()->doctor;
            $result = $this->patientService->deletePatient($doctor, $patientId);

            if (! $result) {
                return ApiResponse::error(
                    message: 'Patient not found or could not be deleted.',
                    status: 404);
            }

            return ApiResponse::success(
                message: 'Patient deleted successfully.'
            );

        } catch (\Exception $e) {
            \Log::error('Error deleting patient: '.$e->getMessage(), ['id' => $patientId]);

            return ApiResponse::error(
                message: 'Failed to delete patient, please try again later.',
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

    public function edit(int $patientId): JsonResponse
    {
        try {
            $doctorId = auth()->user()->doctor->id;

            $patient = $this->patientService->getPatientEditData($doctorId, $patientId);

            return ApiResponse::success(
                message: 'Data retrieved successfully',
                data: new PatientEditResource($patient), status: 200);

        } catch (\Exception $e) {

            \Log::error('Patient Edit Error: '.$e->getMessage(), ['id' => $patientId]);

            return ApiResponse::error(message: 'Failed to retrieve patient data.'.$e->getMessage(), status: 500);
        }
    }

    public function updateStatus(UpdatePatientStatusRequest $request, Patient $patient): JsonResponse
    {

        try {

            $doctorId = auth()->user()->doctor->id;

            $data = $this->patientService->updatePatientStatus($doctorId, $patient, $request->validated()['status']);

            return ApiResponse::success(
                message: 'Patient status updated successfully',
                data: $data,
                status: 200
            );

        } catch (\Exception $e) {

            \Log::error('Patient Status Update Error: '.$e->getMessage(), ['id' => $patient->id]);

            return ApiResponse::error(message: $e->getMessage(), status: $e->getCode() ?: 500);
        }
    }

    public function activityHistory(Request $request, $patientId)
    {
        $doctor = $request->user()->doctor;
        $patient = $doctor->patients()->find($patientId);

        if (! $patient) {
            return ApiResponse::error(
                'You are not allowed to view this patient activities',
                null,
                403
            );
        }

        $logs = ActivityLog::where('patient_id', $patientId)
            ->with('doctor.user')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(
            'Activity history retrieved successfully',
            ActivityLogResource::collection($logs),
            200
        );
    }
}
