<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\V1\Controller;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Helpers\ApiResponse;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;

class ForgetPasswordController extends Controller
{
    public function forgetPassword(ForgetPasswordRequest $request, string $type)
    {
        $validated = $request->validated();
        $fieldType = filter_var($validated['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($fieldType, $validated['identity'])
            ->where('type', $type)
            ->first();

        if (! $user) {
            return ApiResponse::error('Invalid identity', null, 403);
        }
        $user->notify(new ResetPasswordNotification);

        $sentTo = $user->phone ? 'phone number' : 'email address';

        return ApiResponse::success('An OTP has been sent to your '.$sentTo.' for password reset. Please check your inbox.', null, 200);

    }
}
