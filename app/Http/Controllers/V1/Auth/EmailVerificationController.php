<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    public function verifyEmail(EmailVerificationRequest $request, string $type): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->authenticationService->verifyOtp($data, $type);
            if (! $result) {
                return ApiResponse::error(
                    message: 'Invalid OTP or user not found.',
                    status: 400
                );
            }
            return ApiResponse::success(
                message: 'Email has been verified successfully.'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to verify email, please try again later.',
                status: 500
            );
        }
    }
    public function resendOtp(Request $request, string $type): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return ApiResponse::error('User not found.', null, 404);
            }
            $result = $this->authenticationService->resendOtp($user, $type);
            if (! $result) {
                return ApiResponse::error(
                    message: 'Unauthorized action.',
                    status: 403
                );
            }
            $sentTo = $user->contact;
            return ApiResponse::success(
                message: "A new OTP has been sent to your {$sentTo} for verification."
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                message: 'Failed to resend OTP, please try again later.',
                status: 500
            );
        }
    }
}