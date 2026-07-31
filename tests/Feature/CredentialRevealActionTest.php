<?php

use App\Enums\SecretProvider;
use App\Filament\Resources\Credentials\Pages\ListCredentials;
use App\Filament\Resources\Credentials\Pages\ViewCredential;
use App\Models\AuditEvent;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('reveals the secret value, records who retrieved it, and logs an audit event', function () {
    $credential = credentialWithVaultConfig();
    $user = auth()->user();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response([
            'value' => 'super-secret-password',
            'id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc',
        ]),
    ]);

    Livewire::test(ViewCredential::class, ['record' => $credential->getRouteKey()])
        ->callAction('reveal')
        ->assertNotified();

    $credential->refresh();

    expect($credential->last_retrieved_at)->not->toBeNull()
        ->and($credential->last_retrieved_by)->toBe($user->id);

    $event = AuditEvent::withoutGlobalScope(OrganizationScope::class)
        ->where('credential_id', $credential->id)
        ->sole();

    expect($event->action)->toBe('credential_value_retrieved')
        ->and($event->user_id)->toBe($user->id);
});

it('notifies without crashing when the secret is not found', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response(['error' => ['code' => 'SecretNotFound']], 404),
    ]);

    Livewire::test(ViewCredential::class, ['record' => $credential->getRouteKey()])
        ->callAction('reveal')
        ->assertNotified();

    expect(AuditEvent::withoutGlobalScope(OrganizationScope::class)->where('credential_id', $credential->id)->count())->toBe(0);
});

it('notifies without crashing when retrieval fails', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    Livewire::test(ViewCredential::class, ['record' => $credential->getRouteKey()])
        ->callAction('reveal')
        ->assertNotified();

    expect(AuditEvent::withoutGlobalScope(OrganizationScope::class)->where('credential_id', $credential->id)->count())->toBe(0);
});

it('is absent when the credential\'s provider is not Azure Key Vault', function () {
    $credential = credentialWithVaultConfig();
    $credential->update(['secret_provider' => SecretProvider::Bitwarden]);

    Livewire::test(ViewCredential::class, ['record' => $credential->getRouteKey()])
        ->assertActionHidden('reveal');
});

it('is absent when the organization has no vault configured', function () {
    $credential = credentialWithVaultConfig(vaultUrl: null);

    Livewire::test(ViewCredential::class, ['record' => $credential->getRouteKey()])
        ->assertActionHidden('reveal');
});

it('never appears in the credentials list/table', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response(['value' => 'super-secret-password']),
    ]);

    Livewire::test(ListCredentials::class)
        ->assertDontSee('super-secret-password');
});
