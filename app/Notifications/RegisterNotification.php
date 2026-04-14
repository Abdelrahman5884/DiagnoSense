<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class RegisterNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
       if(filter_var($notifiable->contact, FILTER_VALIDATE_EMAIL)) {
           return ['mail'];
       } else {
           return ['vonage'];
       }
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to DiagnoSense')
            ->greeting('Hello '.$notifiable->name.' 💙')
            ->line('Welcome to DiagnoSense. We are excited to have you on board.')
            ->line('Thank you for joining us!');
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Welcome to DiagnoSense 💙. Hello {$notifiable->name}, We are excited to have you on board.");
    }

}
