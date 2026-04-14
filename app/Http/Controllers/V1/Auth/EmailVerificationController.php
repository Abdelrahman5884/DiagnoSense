<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Http\Helpers\ApiResponse;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Ichtrojan\Otp\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    private $otp;

    public function __construct()
    {
        $this->otp = new Otp;
    }

    public function verifyEmail(EmailVerificationRequest $request, string $type)
    {
        $validated = $request->validated();
        $fieldType = filter_var($validated['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($fieldType, $validated['identity'])
            ->where('type', $type)
            ->first();
        if (! $user) {
            return ApiResponse::error('User not found.', null, 404);
        }
        $otp2 = $this->otp->validate($validated['identity'], $validated['otp']);
        if (! $otp2->status) {
            return ApiResponse::error('Invalid or expired OTP.', null, 400);
        }
        $user->update([
            'email_verified_at' => now(),
        ]);

        return ApiResponse::success('Email has been verified successfully.', null, 200);
    }

    public function resendOtp(Request $request, string $type)
    {
        $user = Auth::user();
        if (! $user) {
            return ApiResponse::error('User not found.', null, 404);
        }
        if ($type !== $user->type) {
            return ApiResponse::error('Unauthorized action.', null, 403);
        }
        $request->user()->notify(new EmailVerificationNotification);

        $sentTo = $user->phone ? 'phone number' : 'email';

        return ApiResponse::success('A new OTP has been sent to your '.$sentTo.' for verification. Please check your inbox.', null, 200);
    }
}
