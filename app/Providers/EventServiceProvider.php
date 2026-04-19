<?php

namespace App\Providers;

use App\Events\UserRegistered;
use App\Listeners\SendVerificationEmail;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\OtpRequested;
use App\Listeners\SendOtpNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendVerificationEmail::class,
            SendWelcomeEmail::class,
        ],
        OtpRequested::class => [
            SendOtpNotification::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }
    
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
