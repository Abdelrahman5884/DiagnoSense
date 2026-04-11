<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Resources\FlutterNotificationResource;

class FlutterNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->patient) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $notifications = $this->notificationService
            ->getPatientNotifications($user->patient->id);

        return FlutterNotificationResource::collection($notifications);
    }
}