<?php

namespace App\Providers;

use App\Events\User\UserRegistered;
use App\Listeners\Email\SendVerificationEmail;
use App\Listeners\Email\SendWelcomeEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendVerificationEmail::class,
            SendWelcomeEmail::class,
        ],
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
