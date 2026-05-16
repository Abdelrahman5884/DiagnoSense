<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Models\Plan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $doctor = $request->user()->doctor->load(['wallet', 'activeSubscription.plan']);
        if ($doctor->billing_mode === null) {
            return ApiResponse::error(message: 'No active subscription found. Please subscribe to a plan.', status: 403);
        }
        if ($doctor->billing_mode === 'pay-per-use') {
            if (! $doctor->wallet || $doctor->wallet->balance < Plan::PAY_PER_USE_PRICE) {
                return ApiResponse::error(message: 'Insufficient credits. Please recharge to use Pay-Per-Use (E£'.Plan::PAY_PER_USE_PRICE.'/file).', status: 403);
            }
        } else {
            $subscription = $doctor->activeSubscription;

            if (! $subscription) {
                return ApiResponse::error(message: 'No active subscription found. Please subscribe to a plan.', status: 403);
            }

            if ($subscription->expires_at->isPast()) {
                return ApiResponse::error(message: 'Your subscription has expired. Please renew.', status: 403);
            }

            if ($subscription->used_summaries >= $subscription->plan->summaries_limit) {
                return ApiResponse::error(message: "You have reached your plan limit ({$subscription->plan->summaries_limit} summaries).", status: 403);
            }
        }

        return $next($request);
    }
}
