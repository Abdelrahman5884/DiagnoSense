<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;


class ResetPasswordController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    public function forgotPassword(ForgetPasswordRequest $request, string $type): JsonResponse
    {
        try {
            $data = $request->validated();
            $status = $this->authenticationService->forgotPassword($data, $type);

            if (!$status) {
                return ApiResponse::error(message:'User not found with these credentials.',status: 404);
            }

            return ApiResponse::success(message:'OTP has been sent to your registered contact.');
        } catch (\Exception $e) {
            \Log::error("Forget Password Error: " . $e->getMessage());
            return ApiResponse::error(message:'Failed to process request.',status: 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request, string $type): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->authenticationService->verifyOtp($data, $type);

            if (! $result) {
                return ApiResponse::error(
                    message: 'Invalid or expired OTP.',
                    status: 400
                );
            }

            return ApiResponse::success(
                message: 'OTP verified. Use this token to reset your password.',
                data: ['reset_token' => $result]
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to verify OTP.',
                status: 500
            );
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $data = $request->validated();
            
            $this->authenticationService->resetPassword($user, $data['password']);

            return ApiResponse::success(message:'Password has been reset successfully.');
        } catch (\Exception $e) {
            \Log::error("Password Reset Error: " . $e->getMessage());
            return ApiResponse::error(message:'Failed to reset password.',status: 500);
        }
    }
}
