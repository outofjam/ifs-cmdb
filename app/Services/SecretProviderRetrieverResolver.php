<?php

namespace App\Services;

use App\Contracts\RetrievesSecretValue;
use App\Enums\SecretProvider;
use App\Services\AzureKeyVault\AzureKeyVaultSecretRetriever;
use App\Services\Bitwarden\BitwardenSecretRetriever;

/**
 * Maps a SecretProvider to its value retriever, or null if that provider
 * doesn't have one built yet. Mirrors SecretProviderVerifierResolver's
 * shape for the Reveal action.
 */
class SecretProviderRetrieverResolver
{
    public function resolve(SecretProvider $provider): ?RetrievesSecretValue
    {
        return match ($provider) {
            SecretProvider::AzureKeyVault => app(AzureKeyVaultSecretRetriever::class),
            SecretProvider::Bitwarden => app(BitwardenSecretRetriever::class),
            default => null,
        };
    }
}
