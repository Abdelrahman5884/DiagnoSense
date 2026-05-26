<?php

namespace App\Services\Subscription;

use App\Exceptions\Subscription\BillingValidationException;
use App\Models\Doctor;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\Credit\CreditsExhausted;
use App\Notifications\Subscription\PayPerUseActivated;
use App\Notifications\Subscription\PlanSubscribed;
use App\Notifications\Subscription\SubscriptionCancelled;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function subscribeDoctorToPlan(Doctor $doctor, Plan $plan): Subscription
    {

        return DB::transaction(function () use ($doctor, $plan) {
            $doctorWithLock = Doctor::where('id', $doctor->id)->with(['wallet', 'activeSubscription'])->lockForUpdate()->first();
            $this->validateDoctorCanSubscribe($doctor, $plan);
            $this->deductSubscriptionFees($doctorWithLock, $plan);
            $subscription = $this->processSubscriptionRecord($doctorWithLock, $plan);
            $this->recordBillingTransaction($doctorWithLock, $plan);
            DB::afterCommit(function () use ($doctorWithLock, $plan) {
                $this->dispatchSubscriptionNotifications($doctorWithLock, $plan);
            });

            return $subscription;
        });
    }

    private function validateDoctorCanSubscribe(Doctor $doctor, Plan $plan): void
    {
        if ($doctor->activeSubscription && $doctor->activeSubscription->status === 'active') {
            throw new BillingValidationException(__('You already have an active subscription. Please cancel it before subscribing to a new plan.'));
        }

        $balance = $doctor->wallet ? $doctor->wallet->balance : 0;
        if ($balance < $plan->price) {
            $needed = $plan->price - $balance;
            throw new BillingValidationException(__("Insufficient credits. Please recharge EGP{$needed} to your wallet to subscribe to this plan."));
        }
    }

    private function deductSubscriptionFees(Doctor $doctor, Plan $plan): void
    {
        $doctor->wallet->decrement('balance', $plan->price);
        $doctor->update(['billing_mode' => 'subscription']);
    }

    private function processSubscriptionRecord(Doctor $doctor, Plan $plan): Subscription
    {
        return $doctor->subscriptions()->updateOrCreate(
            ['status' => 'active'],
            [
                'plan_id' => $plan->id,
                'started_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'used_summaries' => 0,
            ]
        );
    }

    private function recordBillingTransaction(Doctor $doctor, Plan $plan): void
    {
        $doctor->transactions()->create([
            'amount' => $plan->price,
            'type' => 'subscription',
            'status' => 'completed',
            'sourceable_type' => get_class($plan),
            'sourceable_id' => $plan->id,
            'description' => "Subscribed to {$plan->name} Plan",
        ]);
    }

    private function dispatchSubscriptionNotifications(Doctor $doctor, Plan $plan): void
    {
        $doctor->notify(new PlanSubscribed($plan->name));

        if ($doctor->wallet->refresh()->balance <= 0) {
            $doctor->notify(new CreditsExhausted);
        }
    }

    public function switchToPayPerUseMode(Doctor $doctor): string
    {

        DB::transaction(function () use ($doctor) {
            $doctor->update([
                'billing_mode' => 'pay-per-use',
            ]);

            $doctor->subscriptions()
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                ]);
        });

        DB::afterCommit(function () use ($doctor) {
            $doctor->notify(new PayPerUseActivated);
        });

        return 'Switched to Pay-Per-Use mode. '.Plan::PAY_PER_USE_PRICE.'EGP will be charged per file.';
    }

    public function validateAiAccess(Doctor $doctor): void
    {
        $doctor->loadMissing(['wallet', 'activeSubscription.plan', 'latestSubscription.plan']);

        if (! $doctor->billing_mode) {
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
        if (! $doctor->wallet || $doctor->wallet->balance < Plan::PAY_PER_USE_PRICE) {
            throw new BillingValidationException(
                __('Insufficient credits. Please recharge to use Pay-Per-Use (E£'.Plan::PAY_PER_USE_PRICE.'/file).'),
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

        if (! $latestSub) {
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

    public function cancelDoctorSubscription(Doctor $doctor): string
    {
        $mode = $doctor->billing_mode;

        if ($mode === 'pay-per-use') {
            return $this->handlePayPerUseCancellation($doctor);
        }

        $subscription = $doctor->activeSubscription;

        if (! $subscription || $mode === null) {
            throw new BillingValidationException(__('No active subscription or billing mode found to cancel.'), 404);
        }

        return $this->handleSubscriptionCancellation($doctor, $subscription);
    }

    private function handlePayPerUseCancellation(Doctor $doctor): string
    {
        DB::transaction(function () use ($doctor) {
            $doctor->update(['billing_mode' => null]);
        });

        return __('Pay-Per-Use mode has been disabled. Please subscribe to a plan to continue.');
    }

    private function handleSubscriptionCancellation(Doctor $doctor, Subscription $subscription): string
    {
        $plan = $subscription->plan;
        DB::transaction(function () use ($doctor, $subscription, $plan) {
            $subscription->update(['status' => 'cancelled']);
            $doctor->update(['billing_mode' => null]);
            DB::afterCommit(function () use ($doctor, $plan) {
                $doctor->notify(new SubscriptionCancelled($plan->name));
            });
        });

        return $this->buildCancellationMessage($subscription, $plan);
    }

    private function buildCancellationMessage(Subscription $subscription, Plan $plan): string
    {
        $limitReached = $subscription->used_summaries >= $plan->summaries_limit;

        if ($limitReached) {
            return __('Subscription cancelled. Note: You have already reached your limit of :limit summaries.', [
                'limit' => $plan->summaries_limit,
            ]);
        }

        $remaining = max(0, $plan->summaries_limit - $subscription->used_summaries);

        return __('Subscription cancelled. You can still use your remaining :remaining summaries until :date', [
            'remaining' => $remaining,
            'date' => $subscription->expires_at->format('D, F j, Y'),
        ]);
    }
}
