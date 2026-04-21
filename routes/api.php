<?php

use App\Http\Controllers\V1\Auth\AuthenticatedController;
use App\Http\Controllers\V1\Auth\EmailVerificationController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\Auth\ResetPasswordController;
use App\Http\Controllers\V1\Auth\SocialAuthController;
use App\Http\Controllers\V1\ChatbotController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\DoctorController;
use App\Http\Controllers\V1\FlutterNotificationController;
use App\Http\Controllers\V1\KeyPointController;
use App\Http\Controllers\V1\MedicalFileController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\Patient\PatientController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\StripeWebhookController;
use App\Http\Controllers\V1\SubscriptionController;
use App\Http\Controllers\V1\SupportController;
use App\Http\Controllers\V1\TaskController;
use App\Http\Controllers\V1\VisitController;
use App\Http\Controllers\V1\VisitItemController;
use App\Http\Controllers\V1\WalletController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::middleware('check-user-type')->group(function () {
        Route::post('/login/{type}', [AuthenticatedController::class, 'login'])->middleware('throttle:login')->name('login');
        Route::post('/forget-password/{type}', [ForgetPasswordController::class, 'forgetPassword']);
        Route::post('/verify-otp/{type}', [ResetPasswordController::class, 'verifyOtp']);
        Route::post('/reset-password/{type}', [ResetPasswordController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout/{type}', [AuthenticatedController::class, 'logout'])->name('logout');
            Route::post('/verify-email/{type}', [EmailVerificationController::class, 'verifyEmail']);
            Route::get('/resend-otp/{type}', [EmailVerificationController::class, 'resendOtp']);
        });
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
    Route::get('/patient/next-visit', [PatientController::class, 'nextVisit']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll']);
    Route::post('/chatbot/{patientId}', [ChatbotController::class, 'store'])->middleware('check-ai-access');
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/status-distribution', [DashboardController::class, 'statusDistribution']);
    Route::get('/dashboard/top-diseases', [DashboardController::class, 'topDiseases']);
    Route::get('/dashboard/today-visits', [DashboardController::class, 'todayVisits']);
    Route::patch('/dashboard/{patientId}/attend', [DashboardController::class, 'markAttended']);
    Route::get('/patients/{patientId}', [PatientController::class, 'edit']);
    Route::put('/patients/{patientId}', [PatientController::class, 'update']);
    Route::post('/support', [SupportController::class, 'store']);
    Route::get('/doctors/{doctorId}', [DoctorController::class, 'edit']);
    Route::put('/doctors/{doctorId}', [DoctorController::class, 'update']);
    Route::delete('/doctors/{doctorId}', [DoctorController::class, 'destroy']);
    Route::patch('/change-password', [DoctorController::class, 'changePassword']);
    Route::get('/patients/{patientId}/comparative-analysis', [PatientController::class, 'getComparativeAnalysis']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/patient/tasks', [TaskController::class, 'index']);
    Route::get('/patient/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/patient/tasks/{task}/complete', [TaskController::class, 'complete']);
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::get('/payment-success', function () {
    return response()->json(['message' => 'Payment successful! You can close this tab.']);
})->name('payment.success');

Route::get('/payment-cancel', function () {
    return response()->json(['message' => 'Payment cancelled.']);
})->name('payment.cancel');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/patient/medical-history', [MedicalFileController::class, 'medicalHistoryFiles']);
    Route::get('/patient/lab-reports', [MedicalFileController::class, 'labReports']);
    Route::get('/patient/radiology-reports', [MedicalFileController::class, 'radiologyReports']);
    Route::get('/patient/medications', [MedicalFileController::class, 'medications']);
    Route::get('/patient/timeline', [MedicalFileController::class, 'timeline']);
    Route::get('/patient/notifications', [FlutterNotificationController::class, 'index']);
    Route::put('/patient/profile', [MedicalFileController::class, 'update']);
});

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Broadcast::routes(['middleware' => ['auth:sanctum']]);
