<?php

namespace App\Notifications\Credit;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CreditAdded extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        protected float|int|string $amount,
        protected float|int|string $newBalance,
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
            'title' => 'Your credits have been charged successfully',
            'message' => "Your account has been credited with {$this->amount}. Your new balance is {$this->newBalance}.",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Your credits have been charged successfully',
            'message' => "Your account has been credited with {$this->amount}. Your new balance is {$this->newBalance}.",
        ]);
    }
}
