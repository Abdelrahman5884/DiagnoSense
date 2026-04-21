<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationSMSNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otpCode
    ) {}

    public function via(object $notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Hello {$notifiable->name}, Your OTP for email verification is: {$this->otpCode}. This OTP will expire in 10 minutes.");
    }
}
