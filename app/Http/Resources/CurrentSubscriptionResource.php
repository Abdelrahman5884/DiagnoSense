<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mode = $this->billing_mode;
        $activeSub = $this->activeSubscription;
        $latestSub = $this->latestSubscription;

        $data = [
            'billing_mode' => $mode,
            'wallet_balance' => (float) ($this->wallet->balance ?? 0),
        ];

        if ($mode === 'pay_per_use') {
            return array_merge($data, [
                'price_per_file' => (float) Plan::PAY_PER_USE_PRICE,
                'features' => ['All features included'],
                'display_text' => 'You are currently using the Pay-Per-Use plan.',
            ]);
        }



        if ($activeSub) {
            $plan = $activeSub->plan;
            return array_merge($data, [
                'plan_name' => $plan->name,
                'status' => $activeSub->status,
                'usage' => [
                    'used' => $activeSub->used_summaries,
                    'total' => $plan->summaries_limit,
                    'remaining' => max(0, $plan->summaries_limit - $activeSub->used_summaries),
                    'percentage' => round(($activeSub->used_summaries / $plan->summaries_limit) * 100, 2),
                ],
                'starts_at' => $activeSub->started_at->format('D, F j, Y'),
                'expires_at' => $activeSub->expires_at->format('D, F j, Y'),
                'features' => is_string($plan->features) ? json_decode($plan->features) : $plan->features,
            ]);
        }

        if ($latestSub) {
            $plan = $latestSub->plan;
            return array_merge($data, [
                'plan_name' => $plan->name,
                'status' => 'expired',
                'usage' => [
                    'used' => $latestSub->used_summaries,
                    'total' => $plan->summaries_limit,
                    'remaining' => max(0, $plan->summaries_limit - $latestSub->used_summaries),
                    'percentage' => round(($latestSub->used_summaries / $plan->summaries_limit) * 100, 2),
                ],
                'starts_at' => $latestSub->started_at->format('D, F j, Y'),
                'expires_at' => $latestSub->expires_at->format('D, F j, Y'),
                'features' => is_string($plan->features) ? json_decode($plan->features) : $plan->features,
            ]);
        }

        return $data;
    }
}
