<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidHmacException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Invalid HMAC'], 401);
    }
}
