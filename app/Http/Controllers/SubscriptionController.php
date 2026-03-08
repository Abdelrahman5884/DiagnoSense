<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribePlanRequest;
use App\Http\Responses\ApiResponse;
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
                $subscription,
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

}
