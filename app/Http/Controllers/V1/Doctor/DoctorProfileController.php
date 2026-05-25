<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Actions\Doctor\ChangeDoctorPasswordAction;
use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Doctor\ChangeDoctorPasswordRequest;
use App\Http\Requests\Doctor\DeleteDoctorAccountRequest;
use App\Http\Requests\Doctor\UpdateDoctorProfileRequest;
use App\Http\Resources\DoctorResource;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;

class DoctorProfileController extends Controller
{
    public function __construct(
        protected DoctorService $doctorService
    ) {}

    public function edit(): JsonResponse
    {
        try {
            $user = auth()->user();
            $user = $this->doctorService->getDoctorProfileData($user);

            return ApiResponse::success(message: 'Doctor Information', data: new DoctorResource($user));
        } catch (\Exception $e) {
            \Log::error('Doctor Profile Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to fetch doctor profile', status: 500);
        }
    }

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

    public function destroy(DeleteDoctorAccountRequest $request): JsonResponse
    {
        try {
            $this->doctorService->deleteDoctorAccount(auth()->user());

            return ApiResponse::success(message: 'Account deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Doctor Profile Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to fetch doctor profile', status: 500);
        }

    }

    public function changePassword(ChangeDoctorPasswordRequest $request, ChangeDoctorPasswordAction $action): JsonResponse
    {
        try {
            $action->execute(
                user: $request->user(),
                newPassword: $request->validated()['new_password']
            );

            return ApiResponse::success(message: 'Password changed successfully');
        } catch (\Exception $e) {
            \Log::error('Password Change Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to change password', status: 500);
        }
    }
}
