<?php

namespace App\Http\Middleware;

use App\Exceptions\BillingValidationException;
use App\Helpers\ApiResponse;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAiAccess
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $doctor = $request->user()->doctor;
            $this->subscriptionService->validateAiAccess($doctor);
            return $next($request);
        } catch (BillingValidationException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                status: $e->getStatusCode()
            );
        }
    }
}
