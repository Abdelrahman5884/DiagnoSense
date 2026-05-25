<?php

namespace App\Http\Controllers\V1\Auth;

use App\Actions\Doctor\ChangeDoctorPasswordAction;
use App\Exceptions\Auth\InvalidOtpException;
use App\Exceptions\Auth\InvalidUserTypeException;
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
            $this->authenticationService->forgotPassword($data);

            return ApiResponse::success(message: 'OTP has been sent to your registered contact.');
        } catch (\Exception $e) {
            \Log::error('Forget Password Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to process request.', status: 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request, string $type): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->authenticationService->verifyOtp($data);
            if(!$result)
            {
                return ApiResponse::error(
                    message: 'Invalid Or Expired OTP.',
                    status: 401
                );
            }

            return ApiResponse::success(
                message: 'OTP verified. You can now reset your password.',
                data: ['reset_token' => $result]
            );
        } catch (\Exception $e) {
            \Log::error('Unexpected OTP Error: '.$e->getMessage(), ['exception' => $e]);

            return ApiResponse::error(
                message: 'An unexpected error occurred. Please try again later.',
                status: 500
            );
        }
    }

    public function resetPassword(ResetPasswordRequest $request, ChangeDoctorPasswordAction $action): JsonResponse
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            $action->execute($user, $data['password']);

            return ApiResponse::success(message: 'Password has been reset successfully.');
        } catch (\Exception $e) {
            \Log::error('Password Reset Error: '.$e->getMessage());

            return ApiResponse::error(message: 'Failed to reset password.', status: 500);
        }
    }
}
