<?php

use App\Enums\SecretProvider;
use App\Enums\VerificationStatus;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
use App\Filament\Resources\Environments\RelationManagers\CredentialsRelationManager;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('verifies a reference and records the result', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*/versions*' => Http::response([
            'value' => [['id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc']],
        ]),
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->callAction(TestAction::make('verify')->table($credential));

    $credential->refresh();

    expect($credential->verification_status)->toBe(VerificationStatus::Verified)
        ->and($credential->last_verified_at)->not->toBeNull();
});

it('is absent when the credential\'s provider is not Azure Key Vault', function () {
    $credential = credentialWithVaultConfig();
    $credential->update(['secret_provider' => SecretProvider::Bitwarden]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->assertActionHidden(TestAction::make('verify')->table($credential));
});

it('is absent when the organization has no vault configured', function () {
    $credential = credentialWithVaultConfig(vaultUrl: null);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->assertActionHidden(TestAction::make('verify')->table($credential));
});
