<?php

namespace App\Listeners;

use App\Events\OtpRequested;
use App\Notifications\EmailVerificationNotification;

class SendOtpNotification
{
    public function handle(OtpRequested $event): void
    {
        $event->user->notify(
            new EmailVerificationNotification($event->otp)
        );
    }
}