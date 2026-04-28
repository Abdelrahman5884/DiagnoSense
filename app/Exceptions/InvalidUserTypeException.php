<?php

namespace App\Exceptions;

use Exception;

class InvalidUserTypeException extends Exception
{
    protected $message = 'Unauthorized access: Invalid user type.';

    protected $code = 403;
}
