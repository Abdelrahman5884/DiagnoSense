<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class MissingHmacException extends Exception
{
    public function render(Request $request)
    {
        return response()->json(['error' => 'Missing HMAC'], 400);
    }
}
