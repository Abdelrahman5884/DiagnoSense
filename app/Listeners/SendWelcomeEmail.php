<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        if(filter_var($event->user->contact, FILTER_VALIDATE_EMAIL)) {
            Mail::to($event->user->contact)->send(new WelcomeMail($event->user));
        }

    }
}
