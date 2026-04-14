<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Helpers\ApiResponse;
use App\Models\User;
use Hash;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    private $otp;

    public function __construct()
    {
        $this->otp = new Otp;
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $validated = $request->validated();

        $otp2 = $this->otp->validate($validated['identity'], $validated['otp']);

        if (! $otp2->status) {
            return ApiResponse::error('Invalid or expired OTP.', null, 400);
        }
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['identity' => $validated['identity']],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        return ApiResponse::success('OTP verified. Use this token to reset password.', [
            'reset_token' => $token,
        ], 200);
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
