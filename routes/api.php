<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('check-user-type')->group(function () {
    Route::post('/register/{type}', [RegisterController::class, 'register']);
    Route::post('/login/{type}', [LoginController::class, 'login']);

    Route::post('/forget-password/{type}', [ForgetPasswordController::class, 'forgetPassword']);
    Route::post('/verify-otp/{type}', [ResetPasswordController::class, 'verifyOtp']);
    Route::post('/reset-password/{type}', [ResetPasswordController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout/{type}', [LogoutController::class, 'logout']);
        Route::post('/verify-email/{type}', [EmailVerificationController::class, 'verifyEmail']);
        Route::get('/resend-otp/{type}', [EmailVerificationController::class, 'resendOtp']);
    });

});

Route::controller(SocialAuthController::class)->group(function () {
    Route::get('/google/redirect', 'redirectToGoogle');
    Route::get('/google/callback', 'handleGoogleCallback');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{patientId}/key-info', [PatientController::class, 'getKeyInfo']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::post('/visits/{visit}/items', [VisitItemController::class, 'store']);
});
