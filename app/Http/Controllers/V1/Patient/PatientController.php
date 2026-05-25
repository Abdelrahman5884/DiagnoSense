<?php

namespace App\Http\Controllers\V1\Patient;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Notification\UpdateFcmTokenRequest;
use App\Http\Requests\Patient\DeletePatientRequest;
use App\Http\Requests\Patient\GetPatientDataForUpdateRequest;
use App\Http\Requests\Patient\GetPatientOverviewRequest;
use App\Http\Requests\Patient\PatientListRequest;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Requests\Patient\UpdatePatientStatusRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\Patient\PatientEditResource;
use App\Http\Resources\Patient\PatientOverviewResource;
use App\Http\Resources\Patient\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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


    public function overview(GetPatientOverviewRequest $request, Patient $patient): JsonResponse
    {

        try {

            $patient = $this->patientService
                ->getPatientOverview($patient);

            return ApiResponse::success(
                message: 'Patient retrieved successfully.',
                data: new PatientOverviewResource($patient)
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching patient overview: ' . $e->getMessage(), ['id' => $patient->id]);

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
    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        try{
            $request->user()->update(['fcm_token' => $request->validated()['fcm_token']]);
            return ApiResponse::success(message: 'FCM Token Updated Successfully');
        }catch (\Exception $e) {
            \Log::error('FCM Token Update Error: '.$e->getMessage());
            return ApiResponse::error(message: 'An error occurred while updating the FCM token.', status: 500);
        }
    }

    public function edit(GetPatientDataForUpdateRequest $request, Patient $patient): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;

            $patient = $this->patientService->getPatientEditData($doctor, $patient);

            return ApiResponse::success(
                message: 'Data retrieved successfully',
                data: new PatientEditResource($patient)
            );

        } catch (\Exception $e) {

            \Log::error('Error retrieving patient data for edit: '.$e->getMessage(), ['id' => $patient->id]);

            return ApiResponse::error(message: 'An error occurred while retrieving patient data for edit.', status: 500);
        }
    }

    public function updateStatus(UpdatePatientStatusRequest $request, Patient $patient): JsonResponse
    {

        try {

            $doctor = auth()->user()->doctor;

            $this->patientService->updatePatientStatus($doctor, $patient, $request->validated()['status']);

            return ApiResponse::success(message: 'Patient status updated successfully');

        } catch (\Exception $e) {

            \Log::error('Patient Status Update Error: '.$e->getMessage(), ['id' => $patient->id]);

            return ApiResponse::error(message: 'Failed to update patient status.', status: 500);
        }
    }

    public function activityHistory(Request $request, Patient $patient): JsonResponse
    {

        try {
            $doctor = $request->user()->doctor;
            if (! $doctor) {
                return ApiResponse::error(message: 'Doctor not found', status: 404);
            }
            $logs = $this->patientService->getPatientActivities($doctor->id, $patient);

            return ApiResponse::success(
                message: 'Activity history retrieved successfully',
                data: ActivityLogResource::collection($logs)->response()->getData(true),
            );

        } catch (HttpException $e) {
            return ApiResponse::error(message: $e->getMessage(), status: $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Error retrieving patient activities: '.$e->getMessage(), ['patient_id' => $patient->id]);

            return ApiResponse::error(message: 'An error occurred while retrieving patient activities.', status: 500);
        }
    }
}
