<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

class MobileNotificationService
{
    public function getPatientNotifications(User $user): Collection
    {
        return $user->notifications()
            ->whereNotNull('data->type')
            ->latest()
            ->get();
    }
}
