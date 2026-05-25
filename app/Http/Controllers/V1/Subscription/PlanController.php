<?php

namespace App\Http\Controllers\V1\Subscription;

use App\Actions\Subscription\GetAvailablePlansAction;
use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function __invoke(GetAvailablePlansAction $getAvailablePlansAction): JsonResponse
    {
        try {
            $plansResource = $getAvailablePlansAction->execute();

            return ApiResponse::success(
                message: 'Available plans retrieved successfully',
                data: $plansResource,
            );

        } catch (\Exception $e) {
            \Log::error('Error retrieving plans: '.$e->getMessage());

            return ApiResponse::error(
                message: 'An error occurred while retrieving plans.',
                status: 500
            );
        }
    }
}
