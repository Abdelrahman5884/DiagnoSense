<?php

use App\Http\Controllers\V1\Auth\AuthenticatedController;
use App\Http\Controllers\V1\Auth\ContactVerificationController;
use App\Http\Controllers\V1\Auth\RegisterController;
use App\Http\Controllers\V1\Auth\ResetPasswordController;
use App\Http\Controllers\V1\Auth\SocialAuthController;
use App\Http\Controllers\V1\ChatbotController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\Doctor\DoctorProfileController;
use App\Http\Controllers\V1\KeyPointController;
use App\Http\Controllers\V1\MedicalFileController;
use App\Http\Controllers\V1\Notification\MobileNotificationController;
use App\Http\Controllers\V1\Notification\WebNotificationController;
use App\Http\Controllers\V1\Patient\PatientController;
use App\Http\Controllers\V1\Patient\PatientProfileController;
use App\Http\Controllers\V1\Paymob\PaymobWebhookController;
use App\Http\Controllers\V1\Paymob\WalletController;
use App\Http\Controllers\V1\Subscription\PlanController;
use App\Http\Controllers\V1\Subscription\SubscriptionController;
use App\Http\Controllers\V1\SupportController;
use App\Http\Controllers\V1\TimelineController;
use App\Http\Controllers\V1\Visit\MedicationController;
use App\Http\Controllers\V1\Visit\TaskController;
use App\Http\Controllers\V1\Visit\VisitController;
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
            Route::controller(ResetPasswordController::class)->as('password.')->group(function () {
                Route::post('/forget-password/{type}', 'forgotPassword')->name('forgot');
                Route::post('/verify-otp/{type}', 'verifyOtp')->name('verify');
                Route::post('/reset-password/{type}', 'resetPassword')->name('reset')->middleware(['auth:sanctum', 'abilities:reset-password']);
            });
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
            Route::get('{patient}/edit', 'edit')->name('edit');
            Route::middleware('can:view,patient')->group(function () {
                Route::get('/{patient}/decision-support', 'getDecisionSupport')->name('decision-support');
                Route::get('/{patient}/comparative-analysis', 'getComparativeAnalysis')->name('comparative-analysis');
            });
            Route::patch('/{patient}', 'update')->name('update');
            Route::patch('{patient}/status', 'updateStatus')->name('update-status');
            Route::post('/{patient}/re-analyze', 'triggerAiAnalysis')->name('re-analyze')->middleware('check-ai-access');

        });
        Route::controller(KeyPointController::class)->group(function () {
            Route::get('/{patient}/key-info', 'index')->name('key-info')->middleware('can:view,patient');
            Route::post('/{patient}/key-info', 'store')->name('add-note');
            Route::patch('{patient}/key-info/{keyPoint}', 'update')->name('key-points.update');
            Route::delete('{patient}/key-info/{keyPoint}', 'destroy')->name('key-points.destroy');
        });
    });

    Route::controller(WalletController::class)->middleware('auth:sanctum')->prefix('wallets')->as('wallets.')->group(function () {
        Route::post('charge', 'store')->name('charge');
        Route::get('transactions', 'index')->name('transactions');
    });
    Route::controller(SubscriptionController::class)->middleware('auth:sanctum')->prefix('subscriptions')->as('subscriptions.')->group(function () {
        Route::post('/{plan}/subscribe', 'subscribe')->name('subscribe');
        Route::post('pay-per-use', 'switchToPayPerUse')->name('pay-per-use');
        Route::get('plans', PlanController::class)->name('plans.index');
        Route::get('current', 'current')->name('current');
        Route::post('cancel', 'cancel')->name('cancel');
    });

    Route::post('/patients/{patient}/chatbot/ask', ChatbotController::class)->middleware(['auth:sanctum', 'check-ai-access'])->name('patients.chatbot.ask');
    Route::controller(DashboardController::class)->middleware('auth:sanctum')->prefix('dashboard')->as('dashboard.')->group(function () {
        Route::get('/status-distribution', 'statusDistribution')->name('status-distribution');
        Route::get('/top-diseases', 'topDiseases')->name('top-diseases');
    });

    Route::middleware('auth:sanctum')->prefix('patient')->as('patient.')->group(function () {
        Route::get('medical-files', MedicalFileController::class)->name('medical-files.index');
    });
    Route::patch('/profile',[PatientProfileController::class])->name('profile.update')->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/next-visit', [VisitController::class, 'show'])->name('next-visit');
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::get('/medications', [MedicationController::class, 'index'])->name('medications.index');
        Route::get('/timeline', TimelineController::class)->name('timeline.index');
        Route::patch('/fcm-token', [PatientController::class, 'updateFcmToken'])->name('patients.fcm-token');
        Route::get('/mobile-notifications', MobileNotificationController::class)->name('mobile.notifications');
        Route::get('/patients/{patient}/activities', [PatientController::class, 'activityHistory'])->name('patients.activities');

        Route::get('/patients/{patient}/overview', [PatientController::class, 'overview'])->name('patients.overview');
        Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        Route::get('/dashboard/today-visits', [DashboardController::class, 'todayVisits'])->name('dashboard.todayVisits');
        Route::patch('/visits/{visit}/attend', [VisitController::class, 'attend'])->name('visits.attend');
        Route::controller(WebNotificationController::class)->prefix('notifications')->as('notifications.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/unread-count', 'unreadCount')->name('unreadCount');
            Route::patch('/{notification}/read', 'read')->name('read');
            Route::patch('/read-all', 'readAll')->name('readAll');
            Route::delete('/clear-all', 'clearAll')->name('clearAll');
        });
        Route::controller(DoctorProfileController::class)->prefix('doctors')->group(function () {
            Route::get('/profile/edit', 'edit')->name('doctor.profile.edit');
            Route::patch('/profile', 'update')->name('doctor.profile.update');
            Route::delete('/profile', 'destroy')->name('doctor.profile.destroy');
            Route::patch('/change-password', 'changePassword')->name('doctor.password.update');
        });
        Route::apiResource('patients.visits', VisitController::class)->only(['index', 'store'])->shallow();
        Route::apiResource('visits.medications', MedicationController::class)->only(['store', 'destroy'])->shallow();
        Route::apiResource('visits.tasks', TaskController::class)->only(['store', 'destroy'])->shallow();
        Route::post('/support', SupportController::class)->name('support.create');
    });
});



Route::get('/payment-success', function () {
    return response()->json(['message' => 'Payment successful! You can close this tab.']);
})->name('payment.success');
Route::get('/payment-cancel', function () {
    return response()->json(['message' => 'Payment cancelled.']);
})->name('payment.cancel');
Broadcast::routes(['middleware' => ['auth:sanctum']]);
Route::get('/payment-redirect', function (Request $request) {
    if ($request->query('success') === 'true') {
        return redirect('http://localhost:5173/subscription?status=success');
    }
});
Route::post('/paymob/webhook', [PaymobWebhookController::class, 'handle'])->name('paymob.webhook');
