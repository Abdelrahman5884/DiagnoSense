<?php
namespace App\Services\Notifications;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\CursorPaginator;

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
}
