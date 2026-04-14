<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\FlutterNotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;

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

        if (! $user->patient) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $notifications = $this->notificationService
            ->getPatientNotifications($user->patient->id);

        return FlutterNotificationResource::collection($notifications);
    }
}
