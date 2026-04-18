<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class WelcomeSMSNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage($notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Welcome to DiagnoSense 💙. Hello {$notifiable->name}, We are excited to have you on board.");
    }
}
