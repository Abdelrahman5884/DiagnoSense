<?php

namespace App\Http\Controllers\V1\Subscription;

use App\Exceptions\Subscription\BillingValidationException;
use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Resources\Subscription\CurrentSubscriptionResource;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function subscribe(Request $request,Plan $plan): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            if(!$doctor) return ApiResponse::error(message: 'Doctor profile not found.', status: 404);
            $this->subscriptionService->subscribeDoctorToPlan(
                doctor: $doctor,
                plan: $plan
            );

            return ApiResponse::success(
                message: 'Successfully subscribed to the plan!',
                status: 201
            );

        } catch (BillingValidationException $e) {
            return ApiResponse::error(message: $e->getMessage(), status: $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Subscription Error: '.$e->getMessage(), ['plan_id' => $plan->id]);

            return ApiResponse::error(
                message: 'An error occurred while processing your subscription. Please try again later.',
                status: 500
            );
        }
    }

    public function switchToPayPerUse(Request $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            if (! $doctor) {
                return ApiResponse::error(message: 'Doctor profile not found.', status: 404);
            }

            $message = $this->subscriptionService->switchToPayPerUseMode($doctor);

            return ApiResponse::success(
                message: $message,
            );

        } catch (\Exception $e) {
            \Log::error('Error switching to pay per use mode: '.$e->getMessage(), ['user_id' => auth()->id()]);

            return ApiResponse::error(message: 'An error occurred while switching to pay-per-use mode.', status: 500);
        }
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

    public function cancel(Request $request): JsonResponse
    {
        try {
            $doctor = $request->user()->doctor;
            $doctor->loadMissing(['activeSubscription.plan']);
            $message = $this->subscriptionService->cancelDoctorSubscription($doctor);

            return ApiResponse::success(message: $message);

        } catch (BillingValidationException $e) {
            return ApiResponse::error(message: $e->getMessage(), status: $e->getStatusCode());
        } catch (\Exception $e) {
            \Log::error('Subscription Cancellation Error: '.$e->getMessage());

            return ApiResponse::error(message: 'An error occurred while cancelling your subscription.', status: 500);
        }
    }
}
