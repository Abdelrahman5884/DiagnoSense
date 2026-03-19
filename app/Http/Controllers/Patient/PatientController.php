<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Requests\UpdatePatientStatusRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\DecisionSupportResource;
use App\Http\Resources\KeyPointResource;
use App\Http\Resources\PatientEditResource;
use App\Http\Resources\PatientListResource;
use App\Http\Resources\PatientOverviewResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\ProcessAi;
use App\Models\ActivityLog;
use App\Models\AiAnalysisResult;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $doctor = $request->user()->doctor;
        $patients = $doctor->patients()->with(['user', 'latestAiAnalysisResult'])->paginate(12);

        return PatientListResource::collection($patients);
    }

    public function store(StorePatientRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'name' => $request->name,
                'email' => $request->email ?? null,
                'phone' => $request->phone ?? null,
                'type' => 'patient',
                'password' => Str::random(10),
            ]);

            $patient = Patient::query()->create([
                'user_id' => $user->id,
                'age' => $request->age ?? null,
                'gender' => $request->gender ?? null,
                'national_id' => $request->national_id ?? null,
            ]);

            $patient->doctors()->attach($request->user()->doctor->id);

            $medicalHistory = MedicalHistory::query()->create([
                'patient_id' => $patient->id,
                'is_smoker' => $request->is_smoker ?? null,
                'previous_surgeries' => $request->previous_surgeries ?? null,
                'chronic_diseases' => $request->chronic_diseases ?? null,
                'previous_surgeries_name' => $request->previous_surgeries_name ?? null,
                'medications' => $request->medications ?? null,
                'allergies' => $request->allergies ?? null,
                'family_history' => $request->family_history ?? null,
                'current_complaint' => $request->current_complaint ?? null,
            ]);

            $reportsTypes = ['lab', 'radiology', 'medical_history'];
            $pathsForAI = [
                'lab' => [],
                'radiology' => [],
                'medical_history' => [],
            ];

            foreach ($reportsTypes as $type) {
                if ($request->hasFile($type)) {
                    foreach ($request->file($type) as $file) {
                        $fileName = $file->getClientOriginalName();
                        $uniqueName = time().'_'.Str::random(5).'.'.$file->getClientOriginalExtension();
                        $filePath = Storage::disk('azure')->putFileAs($type, $file, $uniqueName);
                        if (! $filePath) {
                            throw new \Exception("Failed to upload $fileName file to azure blob storage.");
                        }
                        $mimeType = $file->getMimeType();
                        Report::query()->create([
                            'patient_id' => $patient->id,
                            'type' => $type,
                            'file_name' => $fileName,
                            'file_path' => $filePath,
                            'mime_type' => $mimeType,
                        ]);
                        $pathsForAI[$type][] = $filePath;
                    }
                }
            }

            $analysisResult = AiAnalysisResult::create([
                'patient_id' => $patient->id,
                'status' => 'processing',
            ]);

            $doctor = $request->user()->doctor;

            $jobData = [
                'doctor_id' => $doctor->id,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'history' => $medicalHistory->toArray(),
                'file_paths' => $pathsForAI,
                'features' => [
                    'decision_support' => $doctor->hasFeature('Decision Support'),
                ],
            ];

            DB::commit();

            ProcessAi::dispatch($analysisResult->id, $jobData);

            return ApiResponse::success('Patient created successfully and AI analysis is in progress.', [
                'patient_id' => $patient->id,
                'analysis_result_id' => $analysisResult->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Failed to create patient: '.$e->getMessage(), null, 500);
        }
    }

    public function getKeyInfo($patientId)
    {
        $patient = Patient::find($patientId);
        if (! $patient) {
            return ApiResponse::error('Patient not found', null, 404);
        }
        $latestAnalysis = AiAnalysisResult::where('patient_id', $patientId)
            ->latest()
            ->first();
        if (! $latestAnalysis || $latestAnalysis->status === 'processing') {
            return ApiResponse::error('AI analysis is processing now', null, 404);
        }
        if ($latestAnalysis->status === 'failed') {
            return ApiResponse::error(
                'The AI analysis process failed',
                $latestAnalysis->response,
                422
            );
        }
        $keyPoints = $latestAnalysis->keyPoints()
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success('Key Points retrieved successfully.', [
            'high' => KeyPointResource::collection($keyPoints->where('priority', 'high')),
            'medium' => KeyPointResource::collection($keyPoints->where('priority', 'medium')),
            'low' => KeyPointResource::collection($keyPoints->where('priority', 'low')),
        ], 200);
    }

    public function updateStatus(UpdatePatientStatusRequest $request, $patient)
    {
        $doctor = $request->user()->doctor;
        $patient = $doctor->patients()->find($patient);
        if (! $patient) {
            return ApiResponse::error('Unauthorized or patient not found in your list', null, 403);
        }
        $patient->update(['status' => $request->status]);

        return ApiResponse::success(
            'Patient status updated successfully',
            ['status' => $patient->status],
            200
        );
    }

    public function statusByType(Request $request, string $type)
    {
        $allowedTypes = ['critical', 'stable', 'under review'];

        if (! in_array($type, $allowedTypes)) {
            return ApiResponse::error('Invalid filter type', [], 400);
        }

        $doctor = $request->user()->doctor;

        $patients = $doctor->patients()
            ->with(['user', 'latestAiAnalysisResult'])
            ->where('status', $type)
            ->paginate(12);

        return PatientListResource::collection($patients);
    }

    public function overview(Request $request, $patientId)
    {
        $doctor = $request->user()->doctor;
        $patient = $doctor->patients()->with([
            'user',
            'medicalHistory',
            'latestAiAnalysisResult',
        ])->find($patientId);
        if (! $patient) {
            return ApiResponse::error('Unauthorized or patient not found in your list', null, 403);
        }

        return ApiResponse::success('Patient retrieved successfully.', [
            new PatientOverviewResource($patient),
        ], 200);
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

    public function getDecisionSupport($patientId)
    {
        $patient = auth()->user()->doctor->patients()->findorfail($patientId);
        $latestAnalysis = $patient->aiAnalysisResults()
            ->where('status', 'completed')
            ->latest()
            ->first();
        if (! $latestAnalysis) {
            return ApiResponse::error('No AI analysis results found for this patient.', null, 404);
        }
        $decisions = $latestAnalysis->decisionSupports;
        if ($decisions->isEmpty()) {
            return ApiResponse::error('No decision support data available for this analysis.', null, 404);
        }

        return ApiResponse::success('Decision Support retrieved successfully.', DecisionSupportResource::collection($decisions), 200);
    }

    public function destroy($patientId)
    {
        $doctor = auth()->user()->doctor;
        $patient = $doctor->patients()->findOrFail($patientId);
        $patient->delete();

        return ApiResponse::success('Patient deleted successfully.', null, 200);
    }

    public function show($patientId)
    {
        $doctor = auth()->user()->doctor;
        $patient = $doctor->patients()->findOrFail($patientId);
        $patient->load(['user', 'medicalHistory', 'reports']);

        return ApiResponse::success('Data retrieved successfully', new PatientEditResource($patient), 200);
    }

    public function update(UpdatePatientRequest $request, $patientId)
    {
        $request->validated();
        DB::beginTransaction();
        $doctor = auth()->user()->doctor;
        $patient = $doctor->patients()->findOrFail($patientId);
        try {
            $doctor = auth()->user()->doctor;
            $patient = $doctor->patients()->with(['user', 'medicalHistory'])->findOrFail($patientId);

            $patient->user->update($request->only(['name', 'email', 'phone']));

            $patient->update($request->only(['age', 'gender', 'national_id']));

            $oldComplaint = $patient->medicalHistory->current_complaint;

            $patient->medicalHistory->update($request->only([
                'is_smoker', 'previous_surgeries', 'chronic_diseases',
                'previous_surgeries_name', 'medications', 'allergies',
                'family_history', 'current_complaint',
            ]));

            $reportsTypes = ['lab', 'radiology', 'medical_history'];
            $newPathsForAI = [];
            $hasNewFiles = false;

            foreach ($reportsTypes as $type) {
                if ($request->hasFile($type)) {
                    $hasNewFiles = true;
                    foreach ($request->file($type) as $file) {
                        $uniqueName = time().'_'.Str::random(5).'.'.$file->getClientOriginalExtension();
                        $filePath = Storage::disk('azure')->putFileAs($type, $file, $uniqueName);

                        Report::create([
                            'patient_id' => $patient->id,
                            'type' => $type,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $filePath,
                            'mime_type' => $file->getMimeType(),
                        ]);
                        $newPathsForAI[$type][] = $filePath;
                    }
                }
            }

            $complaintChanged = $request->has('current_complaint') && $request->current_complaint !== $oldComplaint;

            if ($hasNewFiles || $complaintChanged) {

                if ($doctor->user->billing_mode == 'pay-per-use' && $doctor->wallet->balance < config('app.pay_per_use_cost')) {
                    throw new \Exception('Insufficient balance for AI analysis. Please recharge your wallet.');
                }

                if ($doctor->user->billing_mode != 'pay-per-use' && ! $doctor->activeSubscription) {
                    throw new \Exception('No active subscription found. Please subscribe to a plan to access AI features.');
                }

                $analysis = AiAnalysisResult::create([
                    'patient_id' => $patient->id,
                    'status' => 'processing',
                ]);

                $jobData = [
                    'doctor_id' => $doctor->id,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'history' => $patient->medicalHistory->fresh()->toArray(),
                    'file_paths' => $newPathsForAI,
                    'features' => ['decision_support' => $doctor->hasFeature('Decision Support')],
                ];

                ProcessAi::dispatch($analysis->id, $jobData);
            }

            DB::commit();

            return ApiResponse::success('Patient file updated successfully', null, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Update failed: '.$e->getMessage(), null, 500);
        }
    }
}
