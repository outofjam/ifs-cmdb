<?php

use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Organization;
use App\Services\AzureKeyVault\AzureKeyVaultSecretRetriever;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('returns the secret value when found', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response([
            'value' => 'super-secret-password',
            'id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc123',
        ]),
    ]);

    $value = (new AzureKeyVaultSecretRetriever)->retrieve(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    expect($value)->toBe('super-secret-password');
});

it('throws SecretNotFoundException when Key Vault returns a 404', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response(['error' => ['code' => 'SecretNotFound']], 404),
    ]);

    expect(fn () => (new AzureKeyVaultSecretRetriever)->retrieve(organizationWithVaultConfig(), 'no-such-secret'))
        ->toThrow(SecretNotFoundException::class);
});

it('throws SecretRetrievalFailedException when the token request is rejected', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    expect(fn () => (new AzureKeyVaultSecretRetriever)->retrieve(organizationWithVaultConfig(), 'acme-uat-ifs-admin'))
        ->toThrow(SecretRetrievalFailedException::class);
});

it('throws SecretRetrievalFailedException when Key Vault returns a server error', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response(['error' => 'internal'], 500),
    ]);

    expect(fn () => (new AzureKeyVaultSecretRetriever)->retrieve(organizationWithVaultConfig(), 'acme-uat-ifs-admin'))
        ->toThrow(SecretRetrievalFailedException::class);
});

it('throws SecretRetrievalFailedException when the connection times out', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    expect(fn () => (new AzureKeyVaultSecretRetriever)->retrieve(organizationWithVaultConfig(), 'acme-uat-ifs-admin'))
        ->toThrow(SecretRetrievalFailedException::class);
});

it('is configured only when the organization has a vault URL', function () {
    $retriever = new AzureKeyVaultSecretRetriever;

    expect($retriever->isConfigured(organizationWithVaultConfig()))->toBeTrue()
        ->and($retriever->isConfigured(Organization::factory()->create()))->toBeFalse();
});
