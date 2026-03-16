<?php

use App\Models\Doctor;
use App\Notifications\SubscriptionExpired;
use App\Notifications\SubscriptionExpiringSoon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:update-status')->daily();

Schedule::call(function () {
    $doctors = Doctor::with('latestSubscription.user')->get();

    foreach ($doctors as $doctor) {
        $sub = $doctor->latestSubscription;
        if (! $sub || ! $sub->expires_at) {
            continue;
        }
        if ($sub->expires_at->isToday()) {
            $doctor->user->notify(new SubscriptionExpired);
        }
        if ($sub->expires_at->isSameDay(now()->addDays(3)) && ! $sub->expiring_soon_sent) {
            $doctor->user->notify(new SubscriptionExpiringSoon);
            $sub->update(['expiring_soon_sent' => true]);
        }
    }
})->daily();
