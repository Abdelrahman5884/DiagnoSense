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
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable->email) $channels[] = 'mail';
        if ($notifiable->phone) $channels[] = 'vonage';

        return $channels;
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

    public function toVonage($notifiable)
    {
        return (new VonageMessage())
            ->content("Welcome to DiagnoSense 💙. Hello {$notifiable->name}, We are excited to have you on board.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
