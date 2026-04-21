<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\DestroyKeyPointRequest;
use App\Http\Requests\StoreManualNoteRequest;
use App\Http\Requests\UpdateKeyPointRequest;
use App\Http\Resources\KeyPointResource;
use App\Models\AiAnalysisResult;
use App\Models\KeyPoint;

class KeyPointController extends Controller
{
    public function destroy(DestroyKeyPointRequest $request, $keyPointId)
    {
        $keyPoint = KeyPoint::findOrFail($keyPointId);
        $keyPoint->delete();

        return ApiResponse::success('Key point deleted successfully', null, 200);
    }

    public function update(UpdateKeyPointRequest $request, $keyPointId)
    {
        $keyPoint = KeyPoint::findOrFail($keyPointId);
        $validated = $request->validated();
        $keyPoint->update([
            'insight' => $validated['insight'],
        ]);

        return ApiResponse::success('Key point updated successfully', ['id' => $keyPoint->id, 'insight' => $keyPoint->insight], 200);
    }

    public function store(StoreManualNoteRequest $request, $patientId)
    {
        $validated = $request->validated();
        $latestAnalysis = AiAnalysisResult::where('patient_id', $patientId)
            ->where('status', 'completed')
            ->latest()
            ->first();
        if (! $latestAnalysis) {
            return ApiResponse::error('Cannot add note: No completed Profile found for this patient.', null, 422);
        }
        $keyPoint = $latestAnalysis->keyPoints()->create([
            'insight' => $validated['insight'],
            'priority' => $validated['priority'],
            'is_manual' => true,
        ]);

        return ApiResponse::success('Doctor Manual key point added successfully', new KeyPointResource($keyPoint), 201);
    }
}
