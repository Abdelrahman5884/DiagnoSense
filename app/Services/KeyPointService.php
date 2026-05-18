<?php

namespace App\Services;

use App\Helpers\FileSystem;
use App\Http\Resources\KeyPointResource;
use App\Models\AiAnalysisResult;
use App\Models\KeyPoint;
use App\Models\Patient;
use Illuminate\Support\Collection;

class KeyPointService
{
    public function __construct(
        protected PatientService $patientService
    ) {}

    public function getPatientKeyInfo(Patient $patient): array
    {
        $allAnalyses = $this->fetchAnalysesWithKeyPoints($patient);
        $latestAnalysis = $allAnalyses->first();
        $analysesWithData = $this->filterAnalysesWithData($allAnalyses);

        $hasCurrentData = $latestAnalysis?->keyPoints->isNotEmpty() ?? false;
        $hasOldData = $this->hasHistoricalKeyPoints($allAnalyses, $latestAnalysis);
        $isStillProcessing = $latestAnalysis?->status === 'processing';

        $ocrFiles = $this->extractOcrTemporaryUrls($analysesWithData);
        $allKeyPoints = $this->extractAndSortKeyPoints($analysesWithData);

        return [
            'message' => $this->patientService->determineStatusMessage($hasCurrentData, $hasOldData, $isStillProcessing, 'key points'),
            'data' => [
                'still_processing' => $isStillProcessing && ! $hasCurrentData,
                'ocr_files' => $ocrFiles,
                'key_points' => $this->groupKeyPointsByPriority($allKeyPoints),
            ],
        ];
    }

    private function fetchAnalysesWithKeyPoints(Patient $patient): Collection
    {
        return $patient->aiAnalysisResults()->with('keyPoints')->latest()->get();
    }

    private function filterAnalysesWithData(Collection $analyses): Collection
    {
        return $analyses->filter(fn ($analysis) => $analysis->keyPoints->isNotEmpty());
    }

    private function hasHistoricalKeyPoints(Collection $allAnalyses, ?AiAnalysisResult $latestAnalysis): bool
    {
        if (! $latestAnalysis) {
            return false;
        }

        return $allAnalyses->where('id', '!=', $latestAnalysis->id)
            ->flatMap->keyPoints
            ->isNotEmpty();
    }

    private function extractOcrTemporaryUrls(Collection $analysesWithData): array
    {
        return $analysesWithData->map(function ($analysis) {
            return $analysis->ocr_file_path
                ? FileSystem::getTempUrl($analysis->ocr_file_path)
                : null;
        })->filter()->values()->all();
    }

    private function extractAndSortKeyPoints(Collection $analysesWithData): Collection
    {
        return $analysesWithData->flatMap->keyPoints->sortByDesc('created_at');
    }

    private function groupKeyPointsByPriority(Collection $allKeyPoints): array
    {
        return [
            'high' => KeyPointResource::collection($allAllKeyPoints ?? $allKeyPoints->where('priority', 'high')),
            'medium' => KeyPointResource::collection($allKeyPoints->where('priority', 'medium')),
            'low' => KeyPointResource::collection($allKeyPoints->where('priority', 'low')),
        ];
    }

    public function storeManualNote(Patient $patient, array $data): KeyPoint
    {
        $latestAnalysis = $patient->latestAiAnalysisResult;
        if (! $latestAnalysis) {
            throw new \Exception('Cannot add note: No completed analysis found for this patient.', 422);
        }

        return $latestAnalysis->keyPoints()->create([
            'insight' => $data['insight'],
            'priority' => $data['priority'],
            'is_ai_generated' => false,
        ]);
    }
}
