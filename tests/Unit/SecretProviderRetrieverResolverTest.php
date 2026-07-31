<?php

use App\Enums\SecretProvider;
use App\Services\AzureKeyVault\AzureKeyVaultSecretRetriever;
use App\Services\Bitwarden\BitwardenSecretRetriever;
use App\Services\SecretProviderRetrieverResolver;

it('resolves Azure Key Vault to its retriever', function () {
    $retriever = (new SecretProviderRetrieverResolver)->resolve(SecretProvider::AzureKeyVault);

    expect($retriever)->toBeInstanceOf(AzureKeyVaultSecretRetriever::class);
});

it('resolves Bitwarden to its retriever', function () {
    $retriever = (new SecretProviderRetrieverResolver)->resolve(SecretProvider::Bitwarden);

    expect($retriever)->toBeInstanceOf(BitwardenSecretRetriever::class);
});

it('resolves every other provider to null', function (SecretProvider $provider) {
    expect((new SecretProviderRetrieverResolver)->resolve($provider))->toBeNull();
})->with([
    SecretProvider::OnePassword,
    SecretProvider::HashicorpVault,
    SecretProvider::AwsSecretsManager,
]);
