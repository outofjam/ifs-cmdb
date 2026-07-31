<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a secret retrieval attempt fails for a reason other than the
 * reference not existing -- auth failure, network error, provider outage.
 */
class SecretRetrievalFailedException extends Exception
{
    public function __construct()
    {
        parent::__construct('The secret could not be retrieved. Try again shortly.');
    }
}
