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
}
