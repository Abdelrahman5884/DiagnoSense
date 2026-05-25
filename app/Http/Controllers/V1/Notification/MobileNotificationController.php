<?php

namespace App\Http\Controllers\V1\Notification;

use App\Helpers\ApiResponse;
use App\Http\Controllers\V1\Controller;
use App\Http\Resources\Notification\MobileNotificationResource;
use App\Services\Notifications\MobileNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{

    public function __construct(
        protected MobileNotificationService $notificationService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $notifications = $this->notificationService->getPatientNotifications(
                user: $request->user(),
            );

            return ApiResponse::success(
                message: __('Notifications retrieved successfully'),
                data: MobileNotificationResource::collection($notifications)->response()->getData(true)
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching patient notifications via Service: ' . $e->getMessage());
            return ApiResponse::error(message: __('Failed to fetch notifications'), status: 500);
        }
    }
}
