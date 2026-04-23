<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Resources\NotificationResource;
use App\Services\Notifications\WebNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected WebNotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $this->notificationService->getPaginatedUserNotifications($request->user()->doctor);

            return ApiResponse::success(
                message: 'Notifications retrieved successfully.',
                data: NotificationResource::collection($notifications)->response()->getData(true)
            );
        } catch (\Exception $e) {
            \Log::error('Failed to fetch notifications: '.$e->getMessage());

            return ApiResponse::error(message: 'Could not load notifications at the moment.', status: 500);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $count = $this->notificationService->getUnreadCount($request->user()->doctor);

            return ApiResponse::success(
                message: 'Unread notifications count retrieved successfully.',
                data: ['unread_count' => $count]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to count notifications: '.$e->getMessage());

            return ApiResponse::error(message: 'Could not retrieve unread count.', status: 500);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->doctor->notifications()->findOrFail($id);
        $notification->markAsRead();

        return ApiResponse::success('Notification marked as read', null, 200);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->doctor->unreadNotifications->markAsRead();

        return ApiResponse::success('All notifications marked as read', null, 200);
    }

    public function clearAll(Request $request)
    {
        $request->user()->doctor->notifications()->delete();

        return ApiResponse::success('All notifications deleted', null, 200);
    }
}
