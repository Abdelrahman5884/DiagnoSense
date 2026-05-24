<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\KeyPoint\DeleteKeyInfoRequest;
use App\Http\Requests\KeyPoint\UpdateKeyPointRequest;
use App\Http\Requests\StoreManualNoteRequest;
use App\Http\Resources\AnalyzeResult\KeyPointResource;
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

    public function destroy(DeleteKeyInfoRequest $request, Patient $patient, KeyPoint $keyPoint): JsonResponse
    {
        try {
            $this->keyPointService->deleteKeyPoint($keyPoint);

            return ApiResponse::success(message: 'Key point deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Error deleting key point: '.$e->getMessage(), ['id' => $keyPoint->id]);

            return ApiResponse::error(message: 'Error while deleting key point', status: 500);
        }
    }

    public function update(UpdateKeyPointRequest $request, Patient $patient, KeyPoint $keyPoint): JsonResponse
    {
        try {
            $this->keyPointService->updateKeyPoint($keyPoint, $request->validated());

            return ApiResponse::success(message: 'Key point updated successfully');

        } catch (\Exception $e) {
            \Log::error('Error updating key point: '.$e->getMessage(), ['id' => $keyPoint->id]);

            return ApiResponse::error(message: 'Error while updating key point', status: 500);
        }
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
