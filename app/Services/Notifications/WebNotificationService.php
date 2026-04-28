<?php

namespace App\Services\Notifications;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Notifications\DatabaseNotification;

class WebNotificationService
{
    public function getPaginatedUserNotifications(Doctor $doctor, int $perPage = 10): CursorPaginator
    {
        return $doctor->notifications()->cursorPaginate($perPage);
    }

    public function getUnreadCount(Doctor $doctor): int
    {
        return $doctor->unreadNotifications()->count();
    }

    public function read(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function readAll(Doctor $doctor): void
    {
        $doctor->unreadNotifications->markAsRead();
    }

    public function clearAll(Doctor $doctor): void
    {
        $doctor->notifications()->delete();
    }
}
