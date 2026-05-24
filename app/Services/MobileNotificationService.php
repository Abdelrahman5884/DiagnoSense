<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
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
