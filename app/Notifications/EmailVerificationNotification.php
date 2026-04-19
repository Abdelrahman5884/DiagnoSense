<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(private string $otp)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

   public function toMail(object $notifiable)
{
    return (new MailMessage)
        ->subject('Verify Your Email')
        ->view('emails.otp', [
            'name' => $notifiable->name,
            'otp' => $this->otp
        ]);
}

    public function toArray(object $notifiable): array
    {
        return [];
    }
}