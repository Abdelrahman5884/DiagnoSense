<?php

namespace App\Helpers;

use App\Models\User;
use Ichtrojan\Otp\Otp;

class Auth
{

    public static  function getToken(User $user): string
    {
        return $user->createToken('auth_token.'.$user->name)->plainTextToken;
    }

    public static function generateOtp(string $contact, Otp $otp): string
    {
        return $otp->generate($contact, 'numeric', 6, 10)->token;
    }

}
