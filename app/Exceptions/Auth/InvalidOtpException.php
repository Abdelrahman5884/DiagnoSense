<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidOtpException extends Exception
{
    protected $message = 'Invalid or expired OTP.';

    protected $code = 401;
}
