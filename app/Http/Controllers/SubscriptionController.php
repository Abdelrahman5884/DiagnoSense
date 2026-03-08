<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribePlanRequest;
use App\Http\Resources\CurrentSubscriptionResource;
use App\Http\Resources\PlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\Plan;
use App\Services\SubscriptionService;
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

        try {
            $doctor = $request->user()->doctor;
            $subscription = $this->subscriptionService->subscribeDoctorToPlan($doctor, $validated['plan_id']);

            return ApiResponse::success(
                "Successfully subscribed to the plan!",
                null,
                201
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                $e->getMessage(),
                null,
                400
            );
        }
    }

    public function switchToPayPerUse(Request $request)
    {
        $this->subscriptionService->setPayPerUseMode($request->user()->doctor);

        return ApiResponse::success(
            "Switched to Pay-Per-Use mode. E£ 25 will be charged per file.",
            null,
            200
        );
    }

    public function index(){
        $plans = Plan::all();
        return ApiResponse::success(
            "Available plans retrieved successfully",
            PlanResource::collection($plans),
            200
        );
    }

    public function current(Request $request){
        $doctor = $request->user()->doctor->load(['wallet', 'subscriptions.plan']);
        if(!$doctor->billing_mode){
            return ApiResponse::error(
                "No active subscription or billing mode found.",
                null,
                404
            );
        }
        return ApiResponse::success(
        "Current billing mode retrieved successfully",
        new CurrentSubscriptionResource($doctor),
        200
        );
    }


    public function cancel(Request $request)
    {
        $doctor = $request->user()->doctor;
        $subscription = $doctor->activeSubscription;

        if (!$subscription) {
            return ApiResponse::error("No active subscription to cancel.", null, 404);
        }

        $subscription->update(['status' => 'cancelled']);

        return ApiResponse::success(
            "Subscription cancelled. You can still use your remaining summaries until " . $subscription->expires_at->format('D, F j, Y'),
            null,
            200
        );
    }

}
