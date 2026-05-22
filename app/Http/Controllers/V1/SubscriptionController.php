<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\SubscribePlanRequest;
use App\Http\Resources\CurrentSubscriptionResource;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Notifications\CreditsExhausted;
use App\Notifications\PlanSubscribed;
use App\Notifications\SubscriptionCancelled;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $service)
    {
        $this->subscriptionService = $service;
    }

    public function subscribe(SubscribePlanRequest $request)
    {
        $validated = $request->validated();
        $doctor = $request->user()->doctor;
        if (! $doctor) {
            return ApiResponse::error('Doctor profile not found.', null, 404);
        }
        $currentSubscription = $doctor->activeSubscription;
        if ($currentSubscription && $currentSubscription->status === 'active') {
            return ApiResponse::error(
                'You already have an active subscription. Please cancel it before subscribing to a new plan.',
                null,
                400
            );
        }
        $current_balance = $doctor->wallet ? $doctor->wallet->balance : 0;
        $plan_cost = Plan::find($validated['plan_id'])->price;
        if ($current_balance < $plan_cost) {
            $needed = $plan_cost - $current_balance;

            return ApiResponse::error(
                "Insufficient balance. Please recharge $needed E£ to your wallet to subscribe to this plan.",
                null,
                400
            );
        }
        $subscription = $this->subscriptionService->subscribeDoctorToPlan($doctor, $validated['plan_id']);
        if (! $subscription) {
            return ApiResponse::error('Failed to process the subscription. Please try again later.', null, 500);
        }
        $doctor->wallet->refresh();
        $request->user()->doctor->notify(new PlanSubscribed($subscription->plan->name));
        if ($doctor->wallet->balance <= 0) {
            $doctor->notify(new CreditsExhausted);
        }

        return ApiResponse::success(
            'Successfully subscribed to the plan!',
            null,
            201
        );

    }

    public function switchToPayPerUse(): JsonResponse
    {
        try {

            $doctor = auth()->user()->doctor;

            $message = $this->subscriptionService
                ->switchToPayPerUseMode($doctor);

            return ApiResponse::success(
                message: $message,
                status: 200
            );

        } catch (\Exception $e) {
            \Log::error('Error switching to pay per use mode: '.$e->getMessage(), ['doctor_id' => auth()->user()->doctor->id]);

            return ApiResponse::error(message: 'An error occurred while switching to pay-per-use mode.', status: 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $plans = $this->subscriptionService->getAllPlans();

            return ApiResponse::success(
                message: 'Available plans retrieved successfully',
                data: PlanResource::collection($plans),
                status: 200
            );

        } catch (\Exception $e) {
            \Log::error('Error retrieving plans: '.$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while retrieving plans.',
                status: 500
            );
        }
    }

    public function current(Request $request)
    {
        $doctor = $request->user()->doctor->load(['subscriptions.plan']);
        if (! $doctor->billing_mode) {
            return ApiResponse::error(
                'No active subscription or billing mode found.',
                null,
                404
            );
        }

        return ApiResponse::success(
            'Current billing mode retrieved successfully',
            new CurrentSubscriptionResource($doctor),
            200
        );
    }

    public function cancel(Request $request)
    {
        $doctor = $request->user()->doctor;
        $mode = $doctor->billing_mode;

        if ($mode === 'pay_per_use') {
            $doctor->update(['billing_mode' => null]);

            return ApiResponse::success('Pay-Per-Use mode has been disabled. Please subscribe to a plan to continue.', null, 200);
        }

        $subscription = $doctor->activeSubscription;

        if (! $subscription || $mode === null) {
            return ApiResponse::error('No active subscription or billing mode found to cancel.', null, 404);
        }

        $limitReached = $subscription->used_summaries >= $subscription->plan->summaries_limit;

        $subscription->update(['status' => 'cancelled']);

        if ($limitReached) {
            $message = "Subscription cancelled. Note: You have already reached your limit of {$subscription->plan->summaries_limit} summaries.";
        } else {
            $remaining = $subscription->plan->summaries_limit - $subscription->used_summaries;
            $message = "Subscription cancelled. You can still use your remaining {$remaining} summaries until ".$subscription->expires_at->format('D, F j, Y');
        }
        $doctor->notify(new SubscriptionCancelled($subscription->plan->name));

        return ApiResponse::success($message, null, 200);
    }
}
