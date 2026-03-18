<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CreditAdded extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $amount;
    protected $newBalance;
    public function __construct($amount, $newBalance)
    {
        $this->amount = $amount;
        $this->newBalance = $newBalance;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database','broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Your credits have been charged successfully',
            'message' => "Your account has been credited with {$this->amount}. Your new balance is {$this->newBalance}."
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage( [
            'title' => 'Your credits have been charged successfully',
            'message' => "Your account has been credited with {$this->amount}. Your new balance is {$this->newBalance}."
        ]);
    }
}
