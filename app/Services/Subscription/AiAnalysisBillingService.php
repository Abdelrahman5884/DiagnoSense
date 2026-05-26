<?php

namespace App\Services\Subscription;

use App\Models\AiAnalysisResult;
use App\Models\Doctor;
use App\Models\Plan;
use App\Notifications\Credit\CreditsExhausted;
use App\Notifications\Subscription\UsageExhausted;
use App\Notifications\Subscription\UsageThresholdReached;

class AiAnalysisBillingService
{
    public function handleBilling(Doctor $doctor, AiAnalysisResult $analysisRecord): void
    {
        if ($doctor->billing_mode === 'subscription' && $doctor->activeSubscription) {
            $this->handleSubscriptionBilling($doctor);
        } else {
            $this->handlePayPerUseBilling($doctor, $analysisRecord);
        }
    }

    private function handleSubscriptionBilling(Doctor $doctor): void
    {
        $doctor->activeSubscription->increment('used_summaries');
        $doctor->activeSubscription->refresh();
        $this->checkAndNotifyUsage($doctor);
    }

    private function handlePayPerUseBilling(Doctor $doctor, AiAnalysisResult $analysisRecord): void
    {
        $doctor->wallet->decrement('balance', Plan::PAY_PER_USE_PRICE);
        $doctor->wallet->refresh();
        if ($doctor->wallet->balance <= 0) {
            $doctor->notify(new CreditsExhausted);
        }
        $doctor->transactions()->create([
            'amount' => Plan::PAY_PER_USE_PRICE,
            'type' => 'usage',
            'status' => 'completed',
            'description' => 'Pay-per-use Analysis File',
            'sourceable_type' => AiAnalysisResult::class,
            'sourceable_id' => $analysisRecord->id,
        ]);
    }

    private function checkAndNotifyUsage(Doctor $doctor): void
    {
        $subscription = $doctor->activeSubscription;
        $totalLimit = $subscription->plan->summaries_limit;
        $usagePercentage = ($subscription->used_summaries / $totalLimit) * 100;
        if ($usagePercentage >= 80 && ! $doctor->notifications()->where('type', UsageThresholdReached::class)->exists()) {
            $doctor->notify(new UsageThresholdReached(80));
        }
        if ($subscription->used_summaries >= $totalLimit) {
            $doctor->notify(new UsageExhausted);
        }
    }
}
