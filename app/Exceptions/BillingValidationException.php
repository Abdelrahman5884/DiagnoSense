<?php
namespace App\Exceptions;

use Exception;

class BillingValidationException extends Exception
{
    protected $code;

    public function __construct(string $message, int $code = 403)
    {
        parent::__construct($message);
        $this->code = $code;
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }
}
