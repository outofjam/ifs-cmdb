<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a Credential's secret_reference doesn't exist in the
 * provider's vault.
 */
class SecretNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('No secret was found for that reference.');
    }
}
