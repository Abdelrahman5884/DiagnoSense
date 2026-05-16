<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class InvalidHmacException extends Exception
{
    public function render(Request $request)
    {
        return response()->json(['error' => 'Invalid HMAC'], 401);
    }
}
