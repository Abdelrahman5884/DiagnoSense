<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->authenticationService->verifyEmail($data);

            if (! $result) {
                return ApiResponse::error(
                    message: 'Invalid or expired OTP.',
                    status: 401
                );
            }

            return ApiResponse::success(
                message: 'User verified successfully.'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to verify email, please try again later.',
                status: 500
            );
        }
    }

    public function resendOtp(): JsonResponse
    {
        try {
            $user = auth()->user();

            $result = $this->authenticationService->resendOtp($user);

            if (! $result) {
                return ApiResponse::error(
                    message: 'User already verified.',
                    status: 400
                );
            }

            return ApiResponse::success(
                message: 'OTP sent successfully.'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to resend OTP, please try again later.',
                status: 500
            );
        }
    }
}
