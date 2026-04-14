<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\V1\Controller;
use App\Http\Helpers\ApiResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function logout(Request $request, string $type)
    {
        $user = $request->user();
        if ($user->type !== $type) {
            return response()->json([
                'status' => false,
                'message' => "Unauthorized: user is not a {$type}.",
            ], 403);
        }
        $user->tokens()->delete();

        return ApiResponse::success('Logout successfully.', null, 200);

    }
}
