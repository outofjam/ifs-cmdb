<?php

namespace App\Services;

use App\Contracts\VerifiesSecretReference;
use App\Enums\SecretProvider;
use App\Services\AzureKeyVault\AzureKeyVaultReferenceVerifier;

/**
 * Maps a SecretProvider to its verifier, or null if that provider doesn't
 * have one built yet. The seam for adding the next provider without
 * touching the Credential UI.
 */
class SecretProviderVerifierResolver
{
    public function resolve(SecretProvider $provider): ?VerifiesSecretReference
    {
        return match ($provider) {
            SecretProvider::AzureKeyVault => app(AzureKeyVaultReferenceVerifier::class),
            default => null,
        };
    }
}
