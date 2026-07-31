<?php

namespace App\Contracts;

use App\Enums\VerificationStatus;
use App\Models\Organization;

/**
 * A port for checking that a Credential's secret_reference actually exists
 * in the org's vault, without ever reading or returning the secret value.
 */
interface VerifiesSecretReference
{
    public function verify(Organization $organization, string $secretReference): VerificationStatus;
}
