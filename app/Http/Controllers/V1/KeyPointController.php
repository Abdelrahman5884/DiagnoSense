<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\DestroyKeyPointRequest;
use App\Http\Requests\StoreManualNoteRequest;
use App\Http\Requests\UpdateKeyPointRequest;
use App\Http\Resources\KeyPointResource;
use App\Models\KeyPoint;
use App\Models\Patient;
use App\Services\KeyPointService;
use Illuminate\Http\JsonResponse;

class KeyPointController extends Controller
{
    public function __construct(protected KeyPointService $keyPointService) {}

    public function index(Patient $patient): JsonResponse
    {
        try {
            $result = $this->keyPointService->getPatientKeyInfo($patient);

            return ApiResponse::success(
                message: $result['message'],
                data: $result['data'],
            );
        } catch (\Exception $e) {
            \Log::error("Error retrieving key info for Patient {$patient->id}: ".$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while fetching key information.',
                status: 500
            );
        }
    }
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

    public function store(StoreManualNoteRequest $request, Patient $patient): JsonResponse
    {
        try {
            $keyPoint = $this->keyPointService->storeManualNote(patient: $patient, data: $request->validated());

            return ApiResponse::success(
                message: 'Doctor Manual key point added successfully',
                data: new KeyPointResource($keyPoint),
                status: 201
            );
        } catch (\Exception $e) {
            \Log::error('Error adding manual note: '.$e->getMessage());

            return ApiResponse::error(message: 'Error while adding manual note', status: 500);
        }
    }
}
