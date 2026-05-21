<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\BillingValidationException;
use App\Helpers\ApiResponse;
use App\Http\Requests\SubscribePlanRequest;
use App\Http\Resources\CurrentSubscriptionResource;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Notifications\PayPerUseActivated;
use App\Notifications\SubscriptionCancelled;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function subscribe(SubscribePlanRequest $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            if (! $doctor) {
                return ApiResponse::error(message: 'Doctor profile not found.', status: 404);
            }
            $this->subscriptionService->subscribeDoctorToPlan(
                doctor: $doctor,
                planId: $request->validated()['plan_id']
            );

            return ApiResponse::success(
                message: 'Successfully subscribed to the plan!',
                status: 201
            );

        } catch (BillingValidationException $e) {
            return ApiResponse::error(message: $e->getMessage(), status: $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Subscription Error: '.$e->getMessage(), ['plan_id' => $request->input('plan_id')]);

            return ApiResponse::error(
                message: 'An error occurred while processing your subscription. Please try again later.',
                status: 500
            );
        }
    }

    public function switchToPayPerUse(Request $request)
    {
        $this->subscriptionService->setPayPerUseMode($request->user()->doctor);
        $request->user()->doctor->notify(new PayPerUseActivated);

        return ApiResponse::success(
            'Switched to Pay-Per-Use mode. E£ 25 will be charged per file.',
            null,
            200
        );
    }

    public function index()
    {
        $plans = Plan::all();

        return ApiResponse::success(
            'Available plans retrieved successfully',
            PlanResource::collection($plans),
            200
        );
    }

    public function current(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor->loadMissing(['activeSubscription.plan', 'latestSubscription.plan', 'wallet']);
        if (! $doctor->billing_mode) {
            return ApiResponse::error(
                message: 'No active subscription or billing mode found.',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'Current billing mode retrieved successfully',
            data: new CurrentSubscriptionResource($doctor),
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
