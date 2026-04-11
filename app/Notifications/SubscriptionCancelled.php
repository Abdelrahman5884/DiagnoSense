<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $planName;

    public function __construct($planName)
    {
        $this->planName = $planName;
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Subscription Cancelled',
            'message' => "Your subscription to the {$this->planName} plan has been cancelled successfully.",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Subscription Cancelled',
            'message' => "Your subscription to the {$this->planName} plan has been cancelled successfully.",
        ]);
    }
}
