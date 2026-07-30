<?php

namespace App\Exceptions\Auth;

use Exception;

class OrganizationNotApprovedException extends Exception
{
    public function __construct(public readonly string $domain)
    {
        parent::__construct("No organization is approved for the domain [{$domain}].");
    }
}
