<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $otpCode
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (filter_var($notifiable->contact, FILTER_VALIDATE_EMAIL)) {
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
            ->subject('Verify Your Email')
            ->greeting('Hello '.$notifiable->name)
            ->line('Use the following OTP to verify your email:')
            ->line('Your OTP is: **'.$this->otpCode.'**')
            ->line('This OTP will expire in 10 minutes.');
    }

    public function toVonage(object $notifiable): VonageMessage
    {

        return (new VonageMessage)
            ->content("Hello {$notifiable->name}, Your OTP for email verification is: {$this->otpCode}. This OTP will expire in 10 minutes.");
    }
}
