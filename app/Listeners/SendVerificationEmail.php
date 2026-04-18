<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\EmailVerificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        if (filter_var($event->user->contact, FILTER_VALIDATE_EMAIL)) {
            Mail::to($event->user->contact)->send(new EmailVerificationMail($event->user, $event->otpCode));
        }

    }
}
