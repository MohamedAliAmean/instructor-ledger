<?php

namespace App\Exceptions;

use Exception;

class PaymentTimeoutException extends Exception
{
    public function __construct(public readonly string $providerReference)
    {
        parent::__construct('Payment provider timed out after transfer may have succeeded.');
    }
}
