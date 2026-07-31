<?php

use App\Enums\VerificationStatus;
use App\Services\AzureKeyVault\AzureKeyVaultReferenceVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('reports verified when the reference has at least one version', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*/versions*' => Http::response([
            'value' => [['id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc123']],
        ]),
    ]);

    $status = (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    expect($status)->toBe(VerificationStatus::Verified);
});

it('reports not found when Key Vault returns a 404', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*/versions*' => Http::response(['error' => ['code' => 'SecretNotFound']], 404),
    ]);

    $status = (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'no-such-secret');

    expect($status)->toBe(VerificationStatus::NotFound);
});

it('reports failed when the token request is rejected', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    $status = (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    expect($status)->toBe(VerificationStatus::Failed);
});

it('reports failed when Key Vault returns a server error', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*/versions*' => Http::response(['error' => 'internal'], 500),
    ]);

    $status = (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    expect($status)->toBe(VerificationStatus::Failed);
});

it('reports failed when the connection times out', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $status = (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    expect($status)->toBe(VerificationStatus::Failed);
});

it('only ever calls the versions endpoint, never the endpoint that returns the secret value', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*/versions*' => Http::response([
            'value' => [['id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc123', 'attributes' => ['enabled' => true]]],
        ]),
    ]);

    (new AzureKeyVaultReferenceVerifier)->verify(organizationWithVaultConfig(), 'acme-uat-ifs-admin');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/versions'));
});
