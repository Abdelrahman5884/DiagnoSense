<?php

use App\Http\Controllers\V1\Auth\AuthenticatedController;
use App\Http\Controllers\V1\Auth\ContactVerificationController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\Auth\ResetPasswordController;
use App\Http\Controllers\V1\Auth\SocialAuthController;
use App\Http\Controllers\V1\ChatbotController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\Doctor\DoctorProfileController;
use App\Http\Controllers\V1\DoctorController;
use App\Http\Controllers\V1\FlutterNotificationController;
use App\Http\Controllers\V1\KeyPointController;
use App\Http\Controllers\V1\MedicalFileController;
use App\Http\Controllers\V1\MedicationController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PatientController;
use App\Http\Controllers\V1\PaymobWebhookController;
use App\Http\Controllers\V1\SubscriptionController;
use App\Http\Controllers\V1\SupportController;
use App\Http\Controllers\V1\TaskController;
use App\Http\Controllers\V1\VisitController;
use App\Http\Controllers\V1\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class)->name('register');
        Route::controller(SocialAuthController::class)->group(function () {
            Route::get('/google/redirect', 'redirectToGoogle')->name('google.redirect');
            Route::get('/google/callback', 'handleGoogleCallback')->name('google.callback');
        });

        Route::middleware('check-user-type')->group(function () {
            Route::post('/login/{type}', [AuthenticatedController::class, 'login'])->middleware('throttle:login')->name('login');
            Route::post('/forget-password/{type}', [ResetPasswordController::class, 'forgotPassword'])->name('password.forgot');
            Route::post('/verify-otp/{type}', [ResetPasswordController::class, 'verifyOtp'])->name('password.verify');
            Route::post('/reset-password/{type}', [ResetPasswordController::class, 'resetPassword'])->name('password.reset')->middleware(['auth:sanctum', 'abilities:reset-password']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout/{type}', [AuthenticatedController::class, 'logout'])->name('logout');
            Route::post('/verify-contact', [ContactVerificationController::class, 'verifyContact'])->name('verify-contact');
            Route::get('/resend-otp', [ContactVerificationController::class, 'resendOtp'])->name('resend-otp');
        });
    });

    Route::middleware('auth:sanctum')->prefix('patients')->as('patients.')->group(function () {
        Route::controller(PatientController::class)->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('', 'store')->name('store')->middleware('check-ai-access');
            Route::middleware('can:view,patient')->group(function () {
                Route::get('/{patient}/decision-support', 'getDecisionSupport')->name('decision-support');
                Route::get('/{patient}/comparative-analysis', 'getComparativeAnalysis')->name('comparative-analysis');
            });
            Route::patch('/{patient}', 'update')->name('update');
            Route::post('/{patient}/re-analyze', 'triggerAiAnalysis')->name('re-analyze')->middleware('check-ai-access');

        });
        Route::controller(KeyPointController::class)->group(function () {
            Route::get('/{patient}/key-info', 'index')->name('key-info')->middleware('can:view,patient');
            Route::post('/{patient}/key-info', 'store')->name('add-note');
        });
    });

    Route::controller(WalletController::class)->middleware('auth:sanctum')->prefix('wallets')->as('wallets.')->group(function () {
        Route::post('charge', 'store')->name('charge');
        Route::get('transactions', 'index')->name('transactions');
    });
    Route::controller(SubscriptionController::class)->middleware('auth:sanctum')->prefix('subscriptions')->as('subscriptions.')->group(function () {
        Route::post('/subscribe', 'subscribe')->name('subscribe');
        Route::get('/current', 'current')->name('current');
        Route::post('/cancel', 'cancel')->name('cancel');
    });
    Route::controller(DashboardController::class)->middleware('auth:sanctum')->prefix('dashboard')->as('dashboard.')->group(function () {
        Route::get('/status-distribution', 'statusDistribution')->name('status-distribution');
        Route::get('/top-diseases', 'topDiseases')->name('top-diseases');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/patients/{patient}/overview', [PatientController::class, 'overview'])->name('patients.overview');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        Route::controller(NotificationController::class)->prefix('notifications')->as('notifications.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/unread-count', 'unreadCount')->name('unreadCount');
            Route::patch('/{notification}/read', 'read')->name('read');
            Route::patch('/read-all', 'readAll')->name('readAll');
            Route::delete('/clear-all', 'clearAll')->name('clearAll');
        });
        Route::prefix('doctors')->group(function () {
            Route::patch('/profile', [DoctorProfileController::class, 'update'])->name('doctor.profile.update');
            Route::patch('/change-password', [DoctorProfileController::class, 'changePassword'])->name('doctor.password.update');
        });
        Route::apiResource('patients.visits', VisitController::class)->only(['index', 'store'])->shallow();
        Route::apiResource('visits.medications', MedicationController::class)->only(['store', 'destroy'])->shallow();
        Route::apiResource('visits.tasks', TaskController::class)->only(['store', 'destroy'])->shallow();
        Route::post('/support', SupportController::class)->name('support.create');
    });
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/patients/{patientId}/key-info', [PatientController::class, 'getKeyInfo']);

    Route::get('/patients/{patientId}/overview', [PatientController::class, 'overview']);
    Route::patch('/patients/{patient}/status', [PatientController::class, 'updateStatus']);
    Route::delete('/key-points/{keyPointId}', [KeyPointController::class, 'destroy']);
    Route::get('/patients/{patient}/activities', [PatientController::class, 'activityHistory']);
    Route::patch('/key-points/{keyPointId}', [KeyPointController::class, 'update']);
    Route::post('/patients/{patientId}/key-info', [KeyPointController::class, 'store']);
    Route::get('/patients/{patientId}/decision-support', [PatientController::class, 'getDecisionSupport']);
    Route::delete('/patients/{patientId}', [PatientController::class, 'destroy']);
    Route::post('/subscription/pay-per-use', [SubscriptionController::class, 'switchToPayPerUse']);
    Route::get('/subscription/plans', [SubscriptionController::class, 'index']);
    Route::get('/patient/next-visit', [PatientController::class, 'nextVisit']);
    Route::post('/chatbot/{patientId}', [ChatbotController::class, 'store'])->middleware('check-ai-access');


    Route::get('/dashboard/today-visits', [DashboardController::class, 'todayVisits']);
    Route::patch('/dashboard/{patientId}/attend', [DashboardController::class, 'markAttended']);
    Route::get('/patients/{patientId}', [PatientController::class, 'edit']);
    Route::post('/support', [SupportController::class, 'store']);
    Route::put('/patients/{patientId}', [PatientController::class, 'update']);
    Route::get('/doctors/{doctorId}', [DoctorController::class, 'edit']);
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

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/payment-redirect', function (Request $request) {
    if ($request->query('success') === 'true') {
        return redirect('http://localhost:5173/subscription?status=success');
    }
});

Route::post('/paymob/webhook', [PaymobWebhookController::class, 'handle'])->name('paymob.webhook');
