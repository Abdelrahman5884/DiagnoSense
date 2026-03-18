<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CreditsExhausted extends Notification implements ShouldBroadcast
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
        return ['database', 'broadcast'];
    }
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Out of Credits!',
            'message' => 'Your balance has reached 0. Please recharge your wallet to continue using DiagnoSense.',
        ];
    }

    public function toBroadcast($notifiable):BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Out of Credits!',
            'message' => 'Your balance has reached 0. Please recharge your wallet to continue using DiagnoSense.',
        ]);
    }
}
