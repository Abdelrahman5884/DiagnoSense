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

    private function getJobData(Patient $patient, Doctor $doctor, MedicalHistory $medicalHistory, array $pathsForAI): array
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
        if (! empty($pathsForAI['lab'])) {
            $chain[] = new ComparativeAnalysis($patient->id, $analysisResult->id);
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
            'message' => $this->determineStatusMessage($hasCurrentData, $hasOldData, $isStillProcessing, 'Key points'),
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
            'message' => $this->determineStatusMessage($hasCurrentDecisions, $hasOldDecisions, $isStillProcessing, 'Decision support'),
            'data' => [
                'still_processing' => $isStillProcessing && ! $hasCurrentDecisions,
                'decisions' => DecisionSupportResource::collection($decisionsToReturn),
            ],
        ];
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
}
