<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;


class PatientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $type,
        protected string $title,
        protected string $body
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (empty($notifiable->fcm_token)) {
            return ['database'];
        }
        return [FcmChannel::class, 'database'];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return (new FcmMessage())
        ->notification((new FcmNotification())
            ->title($this->title)
            ->body($this->body)
        )
        ->data([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'type'         => (string) strtoupper($this->type),
        ]);
    }
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->body,
        ];
    }

}
