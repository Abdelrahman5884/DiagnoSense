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

    public function verifyEmail(EmailVerificationRequest $request, string $type): JsonResponse
    {
        $data = $request->validated();

        $result = $this->authenticationService->verifyEmail($data, $type);

        if (! $result) {
            return ApiResponse::error(
                message: 'Invalid or expired OTP.',
                status: 401
            );
        }

        return ApiResponse::success(
            message: 'User verified successfully.'
        );
    }

    public function resendOtp(string $type): JsonResponse
    {
        $user = auth()->user();

        $result = $this->authenticationService->resendOtp($user, $type);

        if ($result === 'already_verified') {
            return ApiResponse::error(
                message: 'User already verified.',
                status: 400
            );
        }
        if (! $result) {
            return ApiResponse::error(
                message: 'Unauthorized action.',
                status: 403
            );
        }

        return ApiResponse::success(
            message: 'A new OTP has been sent to your contact.'
        );
    }
}
