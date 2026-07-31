<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a Credential is about to be attached to a Production
 * environment. Credential records are non-prod only -- see
 * docs/plan.md §8 and §11.
 */
class CredentialTargetsProductionEnvironmentException extends Exception
{
    public function __construct()
    {
        parent::__construct('Credentials cannot be attached to a Production environment.');
    }
}
