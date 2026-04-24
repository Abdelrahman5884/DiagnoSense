<?php

namespace App\Http\Controllers\V1\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __construct(
        protected AuthenticationService $authenticationService
    ) {}

    public function verifyOtp(VerifyOtpRequest $request, string $type): JsonResponse
    {
        try {
              $data = $request->validated();

              $result = $this->authenticationService->verifyOtp($data, $type);

              return ApiResponse::success(
                 message: 'OTP verified. You can now reset your password.',
                 data: ['reset_token' => $result]
            );

            } catch (\Exception $e) {
                \Log::error('Error during OTP verification: '.$e->getMessage(), ['exception' => $e]);

                 $message = match ($e->getCode()) {
                  403 => 'Unauthorized access: Invalid user type.',
                  401 => 'Invalid or expired OTP.',
                  default => 'Failed to verify OTP.',
           };

               return ApiResponse::error(
               message: $message,
               status: $e->getCode() ?: 500
            );
            }
        }

    public function resetPassword(ResetPasswordRequest $request, string $type)
    {
        $validated = $request->validated();

        $resetData = DB::table('password_reset_tokens')
            ->where('token', $validated['reset_token'])
            ->first();

        if (! $resetData || now()->subHours(1) > $resetData->created_at) {
            return ApiResponse::error('Invalid or expired token.', null, 403);
        }

        $fieldType = filter_var($resetData->identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($fieldType, $resetData->identity)
            ->where('type', $type)
            ->first();
        if (! $user) {
            return ApiResponse::error('Unauthorized attempt.', null, 403);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        DB::table('password_reset_tokens')->where('identity', $resetData->identity)->delete();

        $user->tokens()->delete();

        return ApiResponse::success('Password has been reset successfully.', null, 200);

    }
}
