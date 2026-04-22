<?php

namespace App\Http\Controllers\V1\Doctor;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\ChangeDoctorPasswordRequest;
use App\Services\DoctorService;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function __construct(
        protected DoctorService $doctorService
    ) {}

    public function __invoke(ChangeDoctorPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->doctorService->changePassword(
                user: auth()->user(),
                newPassword: $validated['new_password']
            );

            return ApiResponse::success(message: 'Password changed successfully');
        } catch (\Exception $e) {
            \Log::error('Error changing password: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to change password', status: 500);
        }
    }
}
