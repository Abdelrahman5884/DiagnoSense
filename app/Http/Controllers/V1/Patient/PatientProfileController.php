<?php

namespace App\Http\Controllers\V1\Patient;

use App\Actions\Patient\UpdatePatientProfileAction;
use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Patient\UpdatePatientProfileRequest;
use App\Http\Resources\PatientProfileResource;
use Illuminate\Http\JsonResponse;

class PatientProfileController extends Controller
{
    public function __invoke(UpdatePatientProfileRequest $request, UpdatePatientProfileAction $updatePatientProfileAction): JsonResponse
    {

        try {
            $user = $request->user();
            if (! $user->patient) {
                return ApiResponse::error(message: 'Patient Profile Not Found', status: 404);
            }
            $data = $updatePatientProfileAction->execute($user, $request->validated());

            return ApiResponse::success(
                message: 'Profile updated successfully',
                data: new PatientProfileResource($data),
            );

        } catch (\Exception $e) {
            \Log::error('Error updating profile: '.$e->getMessage(), ['user_id' => $request->user()?->id]);

            return ApiResponse::error(
                message: 'An error occurred while updating profile.',
                status: 500
            );
        }
    }
}
