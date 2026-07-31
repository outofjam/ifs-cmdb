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

    /**
     * Whether the org has supplied everything this provider needs (a vault
     * URL, an access token, etc.) -- used to hide the Verify action rather
     * than offer one that would just fail.
     */
    public function isConfigured(Organization $organization): bool;
}
