<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    protected $message = 'Invalid or expired OTP.';
    protected $code = 401;
}