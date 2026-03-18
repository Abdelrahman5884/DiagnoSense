<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->cursorPaginate(10);
        return NotificationResource::collection($notifications);
    }
    public function unreadCount(Request $request)
    {
        return ApiResponse::success("Unread notifications count retrieved successfully", [
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ],200);
    }
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return ApiResponse::success("Notification marked as read", null, 200);
    }
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return ApiResponse::success("All notifications marked as read", null, 200);
    }
    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();
        return ApiResponse::success("All notifications deleted", null, 200);
    }
}
