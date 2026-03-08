<?php
namespace App\Services;

use App\Models\Plan;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionService
{
    public function subscribeDoctorToPlan(Doctor $doctor, int $planId)
    {
        return DB::transaction(function () use ($doctor, $planId) {
            $doctor = Doctor::where('id', $doctor->id)->with('wallet')->lockForUpdate()->first();
            $plan = Plan::findOrFail($planId);

            $wallet = $doctor->wallet;
            if (!$wallet || $wallet->balance < $plan->price) {
                $needed = $plan->price - ($wallet->balance ?? 0);
                throw new Exception("Insufficient credits. You need E£ " . $needed . " more.");
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

}
