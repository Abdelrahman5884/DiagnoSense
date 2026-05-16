<?php

namespace App\Services;

use App\Helpers\FileSystem;
use App\Http\Resources\DecisionSupportResource;
use App\Http\Resources\KeyPointResource;
use App\Jobs\AiAnalysisJob;
use App\Jobs\ComparativeAnalysis;
use App\Models\AiAnalysisResult;
use App\Models\Doctor;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientService
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function getPaginatedPatients(int $doctorId, array $params): LengthAwarePaginator
    {
        $query = User::query()
            ->select(['users.id', 'users.name'])
            ->join('patients', 'patients.user_id', '=', 'users.id')
            ->join('doctor_patient', 'doctor_patient.patient_id', '=', 'patients.id')
            ->where('doctor_patient.doctor_id', $doctorId);

        $query->when(! empty($params['search']), function ($q) use ($params) {
            $term = $params['search'];
            $q->where(function ($sub) use ($term) {
                if (is_numeric($term)) {
                    $sub->where('patients.national_id', 'LIKE', $term.'%');
                } else {
                    $sub->where('users.name', 'LIKE', $term.'%');
                }
            });
        });

        $query->when(! empty($params['status']), function ($q) use ($params) {
            $q->where('patients.status', $params['status']);
        });

        return $query->with([
            'patient:id,user_id,date_of_birth,status,created_at,national_id',
            'patient.latestAiAnalysisResult:id,patient_id,ai_insight',
            'patient.latestVisit',
        ])
            ->latest('users.created_at')
            ->paginate(12)
            ->appends($params);
    }

    public function store(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $doctor = auth()->user()->doctor;
            $user = $this->storeUser($data);
            $patient = $this->storePatient($user, $data);
            $patient->doctors()->attach($doctor->id);
            $medicalHistory = $this->storeMedicalHistory($patient, $data);
            $reportsTypes = ['lab', 'radiology', 'medical_history'];
            $pathsForAI = [
                'lab' => [],
                'radiology' => [],
                'medical_history' => [],
            ];

            $pathsForAI = $this->reportService->getPathsForAI($reportsTypes, $data, $patient, $pathsForAI);
            $analysisResult = $patient->latestAiAnalysisResult()->create([
                'status' => 'processing',
            ]);

            $jobData = $this->getJobData($patient, $doctor, $medicalHistory, $pathsForAI);

            $this->triggerAnalysisWorkflows($analysisResult, $jobData, $pathsForAI, $patient);

            return compact('patient', 'analysisResult');
        });
    }

    private function storeUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'contact' => $data['contact'],
            'type' => 'patient',
            'password' => Str::random(10),
        ]);

        return $user;
    }

    private function storePatient(User $user, array $data): Patient
    {
        $patient = $user->patient()->create([
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'] ?? null,
            'national_id' => $data['national_id'] ?? null,
        ]);

        return $patient;
    }

    private function storeMedicalHistory(Patient $patient, array $data): MedicalHistory
    {
        $medicalHistory = $patient->medicalHistory()->create([
            'is_smoker' => $data['is_smoker'] ?? null,
            'previous_surgeries_name' => $data['previous_surgeries_name'] ?? null,
            'chronic_diseases' => $data['chronic_diseases'] ?? null,
            'current_medications' => $data['current_medications'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'family_history' => $data['family_history'] ?? null,
            'current_complaints' => $data['current_complaints'] ?? null,
        ]);

        return $medicalHistory;
    }

    public function runAiAnalysis(Patient $patient, array $newPaths = [], bool $isReAnalysis = false): AiAnalysisResult
    {
        $doctor = auth()->user()->doctor;

        $this->checkBillingPlan($doctor);

        if ($isReAnalysis) {
            $analysisResult = $patient->latestAiAnalysisResult;

            if (! $analysisResult) {
                throw new \Exception('No existing analysis found to upgrade.', 422);
            }

            $analysisResult->update(['status' => 'processing']);
        } else {
            $analysisResult = $patient->latestAiAnalysisResult()->create([
                'status' => 'processing',
            ]);
        }

        $allPaths = $newPaths;
        if (empty(array_filter($newPaths))) {
            $allPaths = $patient->reports->groupBy('type')->map(fn ($group) => $group->pluck('file_path')->toArray())->toArray();
        }

        $jobData = $this->getJobData($patient, $doctor, $patient->medicalHistory, $allPaths, $isReAnalysis);

        $this->triggerAnalysisWorkflows($analysisResult, $jobData, $allPaths, $patient);

        return $analysisResult;
    }

    private function getJobData(Patient $patient, Doctor $doctor, MedicalHistory $medicalHistory, array $pathsForAI, bool $isReAnalysis = false): array
    {
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
            'isReAnalysis' => $isReAnalysis,
        ];

        return $jobData;
    }

    private function triggerAnalysisWorkflows(
        AiAnalysisResult $analysisResult,
        array $jobData,
        array $pathsForAI,
        Patient $patient
    ): void {
        $chain = [
            new AiAnalysisJob($analysisResult->id, $jobData),
        ];
        $isReAnalysis = $jobData['isReAnalysis'] ?? false;
        if (! empty($pathsForAI['lab']) && ! $isReAnalysis) {
            $chain[] = new ComparativeAnalysis($patient, $analysisResult);
        }
        DB::afterCommit(function () use ($chain) {
            Bus::chain($chain)->dispatch();
        });
    }

    public function getPatientKeyInfo(Patient $patient): array
    {
        $allAnalyses = $patient->aiAnalysisResults()->with('keyPoints')->latest()->get();
        $latestAnalysis = $allAnalyses->first();
        $hasCurrentData = $latestAnalysis?->keyPoints->isNotEmpty() ?? false;
        $hasOldData = $allAnalyses->where('id', '!=', $latestAnalysis?->id)->flatMap->keyPoints->isNotEmpty();
        $isStillProcessing = $latestAnalysis?->status === 'processing';
        $analysesWithData = $allAnalyses->filter(fn ($a) => $a->keyPoints->isNotEmpty());

        $ocrFiles = $analysesWithData->map(function ($analysis) {
            return $analysis->ocr_file_path
                ? FileSystem::getTempUrl($analysis->ocr_file_path)
                : null;
        })->filter()->values()->all();
        $allKeyPoints = $analysesWithData->flatMap->keyPoints->sortByDesc('created_at');

        return [
            'message' => $this->determineStatusMessage($hasCurrentData, $hasOldData, $isStillProcessing, 'key points'),
            'data' => [
                'still_processing' => $isStillProcessing && ! $hasCurrentData,
                'ocr_files' => $ocrFiles,
                'key_points' => [
                    'high' => KeyPointResource::collection($allKeyPoints->where('priority', 'high')),
                    'medium' => KeyPointResource::collection($allKeyPoints->where('priority', 'medium')),
                    'low' => KeyPointResource::collection($allKeyPoints->where('priority', 'low')),
                ],
            ],
        ];
    }

    public function getPatientDecisionSupport(Patient $patient): array
    {
        $latestAnalysis = $patient->latestAiAnalysisResult()->with('decisionSupports')->first();
        $isStillProcessing = $latestAnalysis?->status === 'processing';
        $hasCurrentDecisions = $latestAnalysis?->decisionSupports->isNotEmpty() ?? false;
        $oldAnalysis = $patient->aiAnalysisResults()
            ->where('id', '!=', $latestAnalysis?->id)
            ->where('status', 'completed')
            ->latest()
            ->first();
        $hasOldDecisions = $oldAnalysis?->decisionSupports->isNotEmpty() ?? false;
        $decisionsToReturn = collect();
        if ($hasCurrentDecisions) {
            $decisionsToReturn = $latestAnalysis->decisionSupports;
        } elseif ($hasOldDecisions) {
            $decisionsToReturn = $oldAnalysis->decisionSupports;
        }

        return [
            'message' => $this->determineStatusMessage($hasCurrentDecisions, $hasOldDecisions, $isStillProcessing, 'decision support'),
            'data' => [
                'still_processing' => $isStillProcessing && ! $hasCurrentDecisions,
                'decisions' => DecisionSupportResource::collection($decisionsToReturn),
            ],
        ];
    }

    public function getPatientComparativeAnalysis(Patient $patient): array
    {
        $latestAnalysis = $patient->latestAiAnalysisResult;
        $isProcessing = $latestAnalysis?->status === 'processing';
        $allResults = $patient->labResults()->orderBy('created_at', 'asc')->get();

        if ($allResults->isEmpty() && ! $isProcessing) {
            return [];
        }

        $analysisResponse = $this->formatComparativeData($allResults->groupBy('standard_name'));

        $message = 'Comparative analysis retrieved successfully.';
        if ($latestAnalysis?->status === 'failed') {
            $message = 'Note: The AI failed to extract data from the latest reports. Showing historical data only.';
        }

        return [
            'message' => $message,
            'data' => [
                'still_processing' => $isProcessing,
                'analysis' => $analysisResponse,
            ],
        ];
    }

    private function formatComparativeData(Collection $groupedResults): Collection
    {
        return $groupedResults->map(function ($testResults, $testName) {
            $count = $testResults->count();
            $currentRecord = $testResults->last();
            $previousRecord = $count > 1 ? $testResults->get($count - 2) : $currentRecord;

            $currentVal = (float) $currentRecord->numeric_value;
            $previousVal = (float) $previousRecord->numeric_value;

            $changeValue = round($currentVal - $previousVal, 2);
            $percentage = $previousVal != 0 ? round(($changeValue / $previousVal) * 100, 1) : 0;

            return [
                'test_name' => $testName,
                'category' => $currentRecord->category,
                'unit' => $currentRecord->unit,
                'comparison' => [
                    'current_value' => $currentVal,
                    'previous_value' => ($count > 1) ? $previousVal : 'Initial',
                    'change_value' => $changeValue,
                    'change_percentage' => $percentage,
                    'trend' => $this->calculateTrend($currentVal, $previousVal),
                    'status' => $currentRecord->status,
                ],
                'all_points' => $testResults->map(fn ($item, $index) => [
                    'visit_label' => 'Visit #'.($index + 1),
                    'value' => (float) $item->numeric_value,
                    'status' => $item->status,
                    'date' => $item->created_at->format('Y-m-d'),
                ])->values(),
            ];
        })->values();
    }

    private function calculateTrend(float $current, float $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'stable';
    }

    private function determineStatusMessage(bool $hasCurrentData, bool $hasOldData, bool $isStillProcessing, string $label): string
    {
        if ($isStillProcessing && $hasCurrentData) {
            return "{$label} retrieved successfully but comparative analysis is still running.";
        }
        if ($isStillProcessing && $hasOldData) {
            return "Showing old {$label}. Some files are still being processed.";
        }
        if ($isStillProcessing) {
            return "AI analysis for {$label} is still running.";
        }

        return $hasOldData || $hasCurrentData ? "{$label} retrieved successfully." : "No {$label} found for this patient.";
    }

    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            $patient->user->update($data);
            $patient->update($data);
            $oldComplaint = $patient->medicalHistory->current_complaints;
            $patient->medicalHistory->update($data);

            $reportsTypes = ['lab', 'radiology', 'medical_history'];
            $newPathsForAI = [
                'lab' => [], 'radiology' => [], 'medical_history' => [],
            ];
            $newPathsForAI = $this->reportService->getPathsForAI($reportsTypes, $data, $patient, $newPathsForAI);
            $hasNewFiles = ! empty(array_filter($newPathsForAI));
            $complaintChanged = isset($data['current_complaints']) && $data['current_complaints'] !== $oldComplaint;

            if ($hasNewFiles || $complaintChanged) {
                $this->runAiAnalysis($patient, $newPathsForAI);
            }

            return $patient;
        });
    }

    private function checkBillingPlan(Doctor $doctor)
    {
        if (! $doctor->billing_mode) {
            throw new \Exception('No billing mode found.');
        }

        if ($doctor->billing_mode == 'pay_per_use' && $doctor->wallet->balance < config('app.pay_per_use_cost')) {
            throw new \Exception('Insufficient balance for AI analysis.');
        }

        if ($doctor->billing_mode == 'subscription' && ! $doctor->activeSubscription) {
            throw new \Exception('No active subscription found.');
        }
    }
}
