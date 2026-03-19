<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeyPointController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitItemController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Broadcast;
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
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store'])->middleware('check-ai-access');
    Route::get('/patients/{patientId}/key-info', [PatientController::class, 'getKeyInfo']);
    Route::post('/visits', [VisitController::class, 'store']);
    Route::post('/visits/{visit}/items', [VisitItemController::class, 'store']);
    Route::get('/patients/{patient}/items', [VisitItemController::class, 'index']);
    Route::delete('/patients/{patient}/medications/{medication}', [VisitItemController::class, 'destroyMedication']);
    Route::delete('/patients/{patient}/tasks/{task}', [VisitItemController::class, 'destroyTask']);
    Route::get('/patients/{patientId}/overview', [PatientController::class, 'overview']);
    Route::patch('/patients/{patient}/status', [PatientController::class, 'updateStatus']);
    Route::get('/patients/status/{type}', [PatientController::class, 'statusByType']);
    Route::get('/search', SearchController::class);
    Route::delete('/key-points/{keyPointId}', [KeyPointController::class, 'destroy']);
    Route::get('/patients/{patient}/activities', [PatientController::class, 'activityHistory']);
    Route::patch('/key-points/{keyPointId}', [KeyPointController::class, 'update']);
    Route::post('/patients/{patientId}/key-info', [KeyPointController::class, 'store']);
    Route::get('/patients/{patientId}/decision-support', [PatientController::class, 'getDecisionSupport']);
    Route::delete('/patients/{patientId}', [PatientController::class, 'destroy']);
    Route::post('/wallet/charge', [WalletController::class, 'store']);
    Route::get('/transactions', [WalletController::class, 'index']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/pay-per-use', [SubscriptionController::class, 'switchToPayPerUse']);
    Route::get('/subscription/plans', [SubscriptionController::class, 'index']);
    Route::get('/subscription/current', [SubscriptionController::class, 'current']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/status-distribution', [DashboardController::class, 'statusDistribution']);
    Route::get('/dashboard/top-diseases', [DashboardController::class, 'topDiseases']);
    Route::get('/dashboard/today-visits', [DashboardController::class, 'todayVisits']);
    Route::patch('/dashboard/{patientId}/attend', [DashboardController::class, 'markAttended']);
    Route::get('/patients/{patientId}', [PatientController::class, 'show']);
    Route::put('/patients/{patientId}', [PatientController::class, 'update']);
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Broadcast::routes(['middleware' => ['auth:sanctum']]);
