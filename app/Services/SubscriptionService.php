<?php

namespace App\Services;

use App\Exceptions\BillingValidationException;
use App\Models\Doctor;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function subscribeDoctorToPlan(Doctor $doctor, int $planId)
    {
        return DB::transaction(function () use ($doctor, $planId) {
            $doctor = Doctor::where('id', $doctor->id)->with('wallet')->lockForUpdate()->first();
            $plan = Plan::findOrFail($planId);

            $wallet = $doctor->wallet;
            if (! $wallet || $wallet->balance < $plan->price) {
                return false;
            }

            $wallet->decrement('balance', $plan->price);
            $doctor->update(['billing_mode' => 'subscription']);

            $subscription = $doctor->subscriptions()->updateOrCreate(
                ['status' => 'active'],
                [
                    'plan_id' => $plan->id,
                    'started_at' => now(),
                    'expires_at' => now()->addDays($plan->duration_days),
                    'used_summaries' => 0,
                ]
            );

            $doctor->transactions()->create([
                'amount' => $plan->price,
                'type' => 'subscription',
                'status' => 'completed',
                'source_type' => get_class($plan),
                'source_id' => $plan->id,
                'description' => "Subscribed to {$plan->name} Plan",
            ]);

            return $subscription;
        });
    }

    public function setPayPerUseMode(Doctor $doctor)
    {
        $doctor->update(['billing_mode' => 'pay_per_use']);

        $doctor->subscriptions()->update(['status' => 'cancelled']);
    }

    public function validateAiAccess(Doctor $doctor): void
    {
        $doctor->loadMissing(['wallet', 'activeSubscription.plan', 'latestSubscription.plan']);

        if (!$doctor->billing_mode) {
            throw new BillingValidationException(__('No billing mode found. Please subscribe to a plan.'), 403);
        }

        if ($doctor->billing_mode === 'pay-per-use') {
            $this->validatePayPerUse($doctor);
        } else {
            $this->validateSubscription($doctor);
        }
    }

    private function validatePayPerUse(Doctor $doctor): void
    {
        if (!$doctor->wallet || $doctor->wallet->balance < Plan::PAY_PER_USE_PRICE) {
            throw new BillingValidationException(
                __("Insufficient credits. Please recharge to use Pay-Per-Use (E£" . Plan::PAY_PER_USE_PRICE . "/file)."),
                403
            );
        }
    }

    private function validateSubscription(Doctor $doctor): void
    {
        if ($doctor->activeSubscription) {
            return;
        }
        $latestSub = $doctor->latestSubscription;

        if (!$latestSub) {
            throw new BillingValidationException(__('No active subscription found. Please subscribe to a plan.'), 403);
        }

        if ($latestSub->expires_at->isPast()) {
            throw new BillingValidationException(__('Your subscription has expired. Please renew.'), 403);
        }

        if ($latestSub->used_summaries >= $latestSub->plan->summaries_limit) {
            throw new BillingValidationException(
                __("You have reached your plan limit ({$latestSub->plan->summaries_limit} summaries)."),
                403
            );
        }
        throw new BillingValidationException(__('No active subscription found. Please subscribe to a plan.'), 403);
    }
}
