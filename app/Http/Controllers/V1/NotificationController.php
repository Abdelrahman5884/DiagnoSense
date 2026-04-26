<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponse;
use App\Http\Requests\MarkNotificationAsReadRequest;
use App\Http\Resources\NotificationResource;
use App\Services\Notifications\WebNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

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

    public function read(MarkNotificationAsReadRequest $request, Databasenotification $notification): JsonResponse
    {
        try {
            $this->notificationService->read($notification);

            return ApiResponse::success(message: 'Notification marked as read');
        } catch (\Exception $e) {
            \Log::error('Failed to mark notification as read: '.$e->getMessage());

            return ApiResponse::error(message: 'Could not mark notification as read.', status: 500);
        }
    }

    public function readAll(Request $request): JsonResponse
    {
        try {
            $this->notificationService->readAll($request->user()->doctor);

            return ApiResponse::success(message: 'All notifications marked as read');
        } catch (\Exception $e) {
            \Log::error('Failed to mark all notifications as read: '.$e->getMessage());

            return ApiResponse::error(message: 'Could not mark all notifications as read.', status: 500);
        }
    }

    public function clearAll(Request $request)
    {
        $request->user()->doctor->notifications()->delete();

        return ApiResponse::success('All notifications deleted', null, 200);
    }
}
