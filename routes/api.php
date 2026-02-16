<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Room\RoomController;
use App\Http\Controllers\Patient\AddPatientController;


Route::middleware('check-user-type')->group(function () {
    Route::post('/register/{type}', [RegisterController::class, 'register']);
    Route::post('/login/{type}', [LoginController::class, 'login']);

    Route::post('/forget-password/{type}', [ForgetPasswordController::class, 'forgetPassword']);
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
    Route::post('patients/{patient}/rooms', [RoomController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/patients', [AddPatientController::class, 'store']);
    Route::post('/patients/{patient}/medical-history', [AddPatientController::class, 'storeMedicalHistory']);
    Route::post('/patients/{patient}/reports',[AddPatientController::class, 'upload']);
});