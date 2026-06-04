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
        Route::controller(SocialAuthController::class)->prefix('google')->as('google.')->group(function () {
            Route::get('/redirect', 'redirectToGoogle')->name('redirect');
            Route::get('/callback', 'handleGoogleCallback')->name('callback');
        });

        Route::middleware('check-user-type')->group(function () {
            Route::controller(AuthenticatedController::class)->group(function () {
                Route::post('/login/{type}','login')->name('login')->middleware('throttle:login');
                Route::post('/logout/{type}', 'logout')->name('logout')->middleware('auth:sanctum');
            });
            Route::controller(ResetPasswordController::class)->as('password.')->group(function () {
                Route::post('/forget-password/{type}', 'forgotPassword')->name('forgot');
                Route::post('/verify-otp/{type}', 'verifyOtp')->name('verify');
                Route::post('/reset-password/{type}', 'resetPassword')->name('reset')->middleware(['auth:sanctum', 'abilities:reset-password']);
            });
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::controller(ContactVerificationController::class)->group(function () {
                Route::post('/verify-contact','verifyContact')->name('verify-contact');
                Route::get('/resend-otp', 'resendOtp')->name('resend-otp');
            });
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(PatientController::class)->prefix('patients')->as('patients.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('', 'store')->name('store')->middleware('check-ai-access');
            Route::get('{patient}/edit', 'edit')->name('edit');
            Route::patch('/{patient}', 'update')->name('update');
            Route::patch('{patient}/status', 'updateStatus')->name('update-status');
            Route::post('/{patient}/re-analyze', 'triggerAiAnalysis')->name('re-analyze')->middleware('check-ai-access');
            Route::get('/{patient}/activities', 'activityHistory')->name('activities');
            Route::get('/{patient}/overview', 'overview')->name('overview');
            Route::delete('/{patient}', 'destroy')->name('destroy');
            Route::middleware('can:view,patient')->group(function () {
                Route::get('/{patient}/decision-support', 'getDecisionSupport')->name('decision-support');
                Route::get('/{patient}/comparative-analysis', 'getComparativeAnalysis')->name('comparative-analysis');
            });
            Route::post('/{patient}/chatbot/ask', ChatbotController::class)->name('chatbot.ask')->middleware( 'check-ai-access');
        });
        Route::apiResource('patients.key-points', KeyPointController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->shallow()->middlewareFor('index', 'can:view,patient');

        Route::controller(WalletController::class)->prefix('wallets')->as('wallets.')->group(function () {
            Route::post('charge', 'store')->name('charge');
            Route::get('transactions', 'index')->name('transactions');
        });

        Route::controller(SubscriptionController::class)->prefix('subscriptions')->as('subscriptions.')->group(function () {
            Route::post('/{plan}/subscribe', 'subscribe')->name('subscribe');
            Route::post('pay-per-use', 'switchToPayPerUse')->name('pay-per-use');
            Route::get('current', 'current')->name('current');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::get('plans', PlanController::class)->name('plans.index');
        });

        Route::controller(DashboardController::class)->prefix('dashboard')->as('dashboard.')->group(function () {
            Route::get('/status-distribution', 'statusDistribution')->name('status-distribution');
            Route::get('/top-diseases', 'topDiseases')->name('top-diseases');
            Route::get('/summary','summary')->name('summary');
            Route::get('/today-visits', 'todayVisits')->name('todayVisits');
        });

        Route::controller(TaskController::class)->prefix('tasks')->as('tasks.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('/{task}', 'show')->name('show');
            Route::patch('/{task}/complete', 'complete')->name('complete');
        });

        Route::controller(VisitController::class)->group(function(){
            Route::get('/next-visit', 'show')->name('next-visit');
            Route::patch('/visits/{visit}/attend', 'attend')->name('visits.attend');
        });

        Route::controller(WebNotificationController::class)->prefix('notifications')->as('notifications.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/unread-count', 'unreadCount')->name('unreadCount');
            Route::patch('/{notification}/read', 'read')->name('read');
            Route::patch('/read-all', 'readAll')->name('readAll');
            Route::delete('/clear-all', 'clearAll')->name('clearAll');
        });

        Route::controller(DoctorProfileController::class)->prefix('doctors')->as('doctor.')->group(function () {
            Route::get('/profile/edit', 'edit')->name('profile.edit');
            Route::patch('/profile', 'update')->name('profile.update');
            Route::delete('/profile', 'destroy')->name('profile.destroy');
            Route::patch('/change-password', 'changePassword')->name('password.update');
        });

        Route::get('patient/medical-files', MedicalFileController::class)->name('patient.medical-files.index');
        Route::patch('/profile',PatientProfileController::class)->name('profile.update');
        Route::get('/medications', [MedicationController::class, 'index'])->name('medications.index');
        Route::get('/timeline', TimelineController::class)->name('timeline.index');
        Route::patch('/fcm-token', [PatientController::class, 'updateFcmToken'])->name('patients.fcm-token');
        Route::get('/mobile-notifications', MobileNotificationController::class)->name('mobile.notifications');
        Route::apiResource('patients.visits', VisitController::class)->only(['index', 'store','edit','update'])->shallow();
        Route::apiResource('visits.medications', MedicationController::class)->only(['store', 'destroy'])->shallow();
        Route::apiResource('visits.tasks', TaskController::class)->only(['store', 'destroy'])->shallow();
        Route::post('/support', SupportController::class)->name('support.create');
    });
});

Route::get('/payment-redirect', function (Request $request) {
    if ($request->query('success') === 'true') {
        return redirect('https://diagnosense.vercel.app/subscription?status=success');
    }
});
Route::post('/paymob/webhook', [PaymobWebhookController::class, 'handle'])->name('paymob.webhook');
Broadcast::routes(['middleware' => ['auth:sanctum']]);

