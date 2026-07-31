<?php

use App\Enums\SecretProvider;
use App\Services\AzureKeyVault\AzureKeyVaultReferenceVerifier;
use App\Services\SecretProviderVerifierResolver;

it('resolves Azure Key Vault to its verifier', function () {
    $verifier = (new SecretProviderVerifierResolver)->resolve(SecretProvider::AzureKeyVault);

    expect($verifier)->toBeInstanceOf(AzureKeyVaultReferenceVerifier::class);
});

it('resolves every other provider to null', function (SecretProvider $provider) {
    expect((new SecretProviderVerifierResolver)->resolve($provider))->toBeNull();
})->with([
    SecretProvider::OnePassword,
    SecretProvider::Bitwarden,
    SecretProvider::HashicorpVault,
    SecretProvider::AwsSecretsManager,
]);

it('lists every provider with a working verifier or retriever as implemented', function () {
    expect((new SecretProviderVerifierResolver)->implementedProviders())
        ->toBe([SecretProvider::AzureKeyVault, SecretProvider::Bitwarden]);
});
