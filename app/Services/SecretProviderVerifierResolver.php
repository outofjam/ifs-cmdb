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

    /**
     * Providers with a working verifier, retriever, or both -- the only
     * ones a Credential should ever be creatable with. Selecting a
     * provider the platform can't actually reach isn't a real choice.
     *
     * @return array<int, SecretProvider>
     */
    public function implementedProviders(): array
    {
        return array_values(array_filter(
            SecretProvider::cases(),
            fn (SecretProvider $provider): bool => $this->resolve($provider) !== null
                || app(SecretProviderRetrieverResolver::class)->resolve($provider) !== null,
        ));
    }
}
