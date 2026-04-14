<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendVerificationEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $event->user->notify(new EmailVerificationNotification($event->otpCode));
    }
}
