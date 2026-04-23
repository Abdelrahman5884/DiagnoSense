<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordSMSNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $otp) {}
    public function via(object $notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Your DiagnoSense password reset code is: {$this->otp}. It expires in 10 minutes.");
    }
}
