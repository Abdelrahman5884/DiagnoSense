<?php

namespace App\Http\Resources\Subscription;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mode = $this->billing_mode;
        $baseData = [
            'billing_mode' => $mode,
            'balance' => (float) ($this->wallet->balance ?? 0),
        ];

        if ($mode === 'pay-per-use') {
            return array_merge($baseData, $this->formatPayPerUseData());
        }

        $activeSub = $this->activeSubscription;
        $latestSub = $this->latestSubscription;

        if ($activeSub) {
            return array_merge($baseData, $this->formatSubscriptionData($activeSub, $activeSub->status));
        }

        if ($latestSub) {
            return array_merge($baseData, $this->formatSubscriptionData($latestSub, 'expired'));
        }

        return $baseData;
    }

    private function formatPayPerUseData(): array
    {
        return [
            'price_per_file' => (float) Plan::PAY_PER_USE_PRICE,
            'features' => ['All features included'],
        ];
    }

    private function formatSubscriptionData($subscription, string $status): array
    {
        $plan = $subscription->plan;

        return [
            'plan_name' => $plan->name,
            'status' => $status,
            'usage' => $this->calculateUsageMetrics($subscription, $plan->summaries_limit),
            'starts_at' => $subscription->started_at->format('D, F j, Y'),
            'expires_at' => $subscription->expires_at->format('D, F j, Y'),
            'features' => is_string($plan->features) ? json_decode($plan->features) : $plan->features,
        ];
    }

    private function calculateUsageMetrics($subscription, int $limit): array
    {
        $used = $subscription->used_summaries;
        $remaining = max(0, $limit - $used);

        return [
            'used' => $used,
            'total' => $limit,
            'remaining' => $remaining,
            'percentage' => $limit > 0 ? round(($used / $limit) * 100, 2) : 0,
        ];
    }
}
