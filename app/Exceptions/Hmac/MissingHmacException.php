<?php

namespace App\Exceptions\Hmac;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissingHmacException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['error' => 'Missing HMAC'], 400);
    }
}
