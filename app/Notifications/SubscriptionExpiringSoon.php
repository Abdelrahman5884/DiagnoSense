<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringSoon extends Notification implements ShouldQueue
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

    public function toDatabase(object $notifiable): array {
        return [
            'title' => 'Subscription Expiring Soon',
            'message' => 'Your plan will expire in 3 days be careful.',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage {
        return new BroadcastMessage([
            'title' => 'Subscription Expiring Soon',
            'message' => 'Your plan will expire in 3 days be careful.',
        ]);
    }

}
