<?php

namespace App\Notifications\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class UsageThresholdReached extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        protected int|float $percentage = 80,
    ) {}

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
            'title' => "Usage Alert: {$this->percentage}% Reached",
            'message' => "You have consumed {$this->percentage}% of your plan's summaries. Top up soon to avoid interruption.",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => "Usage Alert: {$this->percentage}% Reached",
            'message' => "You have consumed {$this->percentage}% of your plan's summaries. Top up soon to avoid interruption.",
        ]);
    }
}
