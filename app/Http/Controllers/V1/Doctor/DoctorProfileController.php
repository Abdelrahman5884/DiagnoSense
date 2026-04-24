<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Actions\Doctor\ChangeDoctorPasswordAction;
use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\ChangeDoctorPasswordRequest;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;

class DoctorProfileController extends Controller
{
    public function __construct(
        protected DoctorService $doctorService
    ) {}

    public function update(UpdateDoctorProfileRequest $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            $this->doctorService->updateProfile(
                doctor: $doctor,
                data: $request->validated()
            );

            return ApiResponse::success(message: 'Profile updated successfully');
        } catch (\Exception $e) {
            \Log::error('Error updating profile: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(message: 'Failed to update profile', status: 500);
        }
    }
    public function changePassword(ChangeDoctorPasswordRequest $request,ChangeDoctorPasswordAction $action): JsonResponse {
        try {
            $action->execute(
                user: $request->user(),
                newPassword: $request->validated()['new_password']
            );

            return ApiResponse::success(message: 'Password changed successfully');
        } catch (\Exception $e) {
            \Log::error('Password Change Error: ' . $e->getMessage());
            return ApiResponse::error(message: 'Failed to change password', status: 500);
        }
    }
}
