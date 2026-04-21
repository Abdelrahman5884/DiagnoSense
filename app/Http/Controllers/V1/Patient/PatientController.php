<?php

namespace App\Http\Controllers\V1\Patient;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Requests\UpdatePatientStatusRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\DecisionSupportResource;
use App\Http\Resources\KeyPointResource;
use App\Http\Resources\NextVisitResource;
use App\Http\Resources\PatientEditResource;
use App\Http\Resources\PatientListResource;
use App\Http\Resources\PatientOverviewResource;
use App\Jobs\ComparativeAnalysis;
use App\Jobs\ProcessAi;
use App\Models\ActivityLog;
use App\Models\AiAnalysisResult;
use App\Models\DecisionSupport;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\PatientLabResult;
use App\Models\Report;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;

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
                'patient_id' => $patient->id,
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

            $chain = [
                new ProcessAi($analysisResult->id, $jobData),
            ];

            if (! empty($pathsForAI['lab'])) {
                $chain[] = new ComparativeAnalysis($patient->id, $analysisResult->id);
            }

            Bus::chain($chain)->dispatch();

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
            $hasData = $latestAnalysis->keyPoints()->exists();
            if (! $hasData) {
                return ApiResponse::error(
                    'The AI analysis process failed and no information was retrieved.',
                    $latestAnalysis->response,
                    422
                );
            }

        }
        if ($latestAnalysis->ocr_file_path) {
            $this->fixAzureBlobProperties($latestAnalysis->ocr_file_path);
        }
        $ocrFileUrl = $latestAnalysis->ocr_file_path ? Storage::disk('azure')->temporaryUrl($latestAnalysis->ocr_file_path, now()->addMinutes(60)) : null;
        $keyPoints = $latestAnalysis->keyPoints()
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success('Key Points retrieved successfully.', [
            'source_file' => $ocrFileUrl,
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

    public function nextVisit(Request $request)
    {
        $user = $request->user();

        $patient = Patient::where('user_id', $user->id)
            ->with('doctors.user')
            ->first();

        if (! $patient) {
            return ApiResponse::error(
                'Patient not found',
                null,
                404
            );
        }

        if (! $patient->next_visit_date || $patient->next_visit_date < now()) {
            return ApiResponse::success(
                'No upcoming visit',
                null,
                200
            );
        }

        $visit = Visit::where('patient_id', $patient->id)
            ->whereDate('next_visit_date', '>=', now())
            ->with('doctor.user')
            ->orderBy('next_visit_date')
            ->first();

        if (! $visit) {
            return ApiResponse::success(
                'No upcoming visit',
                null, 200
            );
        }

        return ApiResponse::success('Next visit retrieved successfully', new NextVisitResource($visit), 200);
    }

    public function edit($patientId)
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
            $doctorHasDS = $doctor->hasFeature('Decision Support');
            $lastSuccessfulAnalysis = AiAnalysisResult::where('patient_id', $patient->id)
                ->where('status', 'completed')
                ->latest()
                ->first();
            $hasExistingDecision = false;
            if ($lastSuccessfulAnalysis) {
                $hasExistingDecision = DecisionSupport::where('ai_analysis_result_id', $lastSuccessfulAnalysis->id)->exists();
            }
            $needsDecisionSupportNow = $doctorHasDS && ! $hasExistingDecision;
            if ($hasNewFiles || $complaintChanged || $needsDecisionSupportNow) {
                if (! $doctor->billing_mode) {
                    throw new \Exception('No billing mode found you can not access AI features.');
                }

                if ($doctor->billing_mode == 'pay_per_use' && $doctor->wallet->balance < config('app.pay_per_use_cost')) {
                    throw new \Exception('Insufficient balance for AI analysis. Please recharge your wallet.');
                }

                if ($doctor->billing_mode == 'subscription' && ! $doctor->activeSubscription) {
                    throw new \Exception('No active subscription found. Please subscribe to a plan to access AI features.');
                }

                $analysis = AiAnalysisResult::create([
                    'patient_id' => $patient->id,
                    'status' => 'processing',
                ]);

                $jobData = [
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'history' => $patient->medicalHistory->fresh()->toArray(),
                    'file_paths' => $newPathsForAI,
                    'features' => ['decision_support' => $doctorHasDS],
                ];

                $chain = [
                    new ProcessAi($analysis->id, $jobData),
                ];
                if (! empty($newPathsForAI['lab'])) {
                    $chain[] = new ComparativeAnalysis($patient->id, $analysis->id);
                }
                Bus::chain($chain)->dispatch();
            }

            DB::commit();

            return ApiResponse::success('Patient file updated successfully', null, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Update failed: '.$e->getMessage(), null, 500);
        }
    }

    public function getComparativeAnalysis($patientId)
    {
        $doctor = auth()->user()->doctor;
        $patient = $doctor->patients()->findorfail($patientId);
        $latestAnalysis = AiAnalysisResult::where('patient_id', $patientId)
            ->latest()
            ->first();
        if ($latestAnalysis && $latestAnalysis->status === 'processing') {
            return ApiResponse::success(
                'The AI is currently analyzing new reports.',
                null,
                202
            );
        }
        $allResults = PatientLabResult::where('patient_id', $patientId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allResults->isEmpty()) {
            return ApiResponse::error('No analysis data found.', null, 404);
        }
        $message = 'Comparative data retrieved successfully.';
        if ($latestAnalysis && $latestAnalysis->status === 'failed') {
            $message = 'Note: The AI failed to extract data from the latest reports. Showing historical data only.';
        }
        $groupedData = $allResults->groupBy('standard_name');
        $analysisResponse = $groupedData->map(function (Collection $testResults, $testName) {
            $count = $testResults->count();
            $currentRecord = $testResults->last();
            $previousRecord = $count > 1 ? $testResults->get($count - 2) : $currentRecord;
            $currentVal = (float) $currentRecord->numeric_value;
            $previousVal = (float) $previousRecord->numeric_value;
            $changeValue = round($currentVal - $previousVal, 2);
            $percentage = $previousVal != 0
                ? round(($changeValue / $previousVal) * 100, 1)
                : 0;
            $trend = 'stable';
            if ($currentVal > $previousVal) {
                $trend = 'up';
            } elseif ($currentVal < $previousVal) {
                $trend = 'down';
            }
            $previousDisplay = ($count > 1) ? $previousVal : 'No previous';

            return [
                'test_name' => $testName,
                'category' => $currentRecord->category,
                'unit' => $currentRecord->unit,
                'comparison' => [
                    'current_value' => $currentVal,
                    'previous_value' => $previousDisplay,
                    'change_value' => $changeValue,
                    'change_percentage' => $percentage,
                    'trend' => $trend,
                    'status' => $currentRecord->status,
                ],
                'all_points' => $testResults->map(function ($item, $index) {
                    return [
                        'visit_label' => 'Visit #'.($index + 1),
                        'value' => (float) $item->numeric_value,
                        'status' => $item->status,
                        'date' => $item->created_at->format('Y-m-d'),
                    ];
                })->values(),
            ];
        })->values();

        return ApiResponse::success($message, $analysisResponse, 200);

    }

    private function fixAzureBlobProperties($blobPath)
    {
        $connectionString = config('filesystems.disks.azure.connection_string');
        $containerName = config('filesystems.disks.azure.container');
        $blobClient = BlobRestProxy::createBlobService($connectionString);
        $contentType = 'application/pdf';
        $properties = new SetBlobPropertiesOptions;
        $properties->setContentType($contentType);
        $properties->setContentDisposition('inline');
        $blobClient->setBlobProperties($containerName, $blobPath, $properties);
    }
}
