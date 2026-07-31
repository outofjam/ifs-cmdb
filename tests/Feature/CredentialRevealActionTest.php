<?php

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
use App\Filament\Resources\Environments\RelationManagers\CredentialsRelationManager;
use App\Models\AuditEvent;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
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

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->callAction(TestAction::make('reveal')->table($credential))
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

it('displays the revealed value as a copyable, monospaced code block', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response([
            'value' => 'super-secret-password',
            'id' => 'https://acme-vault.vault.azure.net/secrets/acme-uat-ifs-admin/abc',
        ]),
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->callAction(TestAction::make('reveal')->table($credential));

    // session()->pull() is read-once, so this reads the flashed notification
    // directly instead of also calling ->assertNotified() first (which
    // would have already drained it via the same pull()).
    $notificationsComponent = new Notifications;
    $notificationsComponent->mount();
    $notification = $notificationsComponent->notifications->sole();

    expect($notification->getBody())
        ->toContain('<pre')
        ->toContain('super-secret-password');

    $copyAction = collect($notification->getActions())->sole(fn ($action) => $action->getName() === 'copy');

    // Must run purely client-side: the Notifications Livewire component
    // doesn't implement HasActions, so the default wire:click="mountAction"
    // handler 500s if it's left enabled alongside a custom one.
    expect($copyAction->isLivewireClickHandlerEnabled())->toBeFalse()
        ->and($copyAction->getAlpineClickHandler())->toContain('super-secret-password')
        ->and($copyAction->getAlpineClickHandler())->toContain('Copied!')
        ->and($copyAction->getAlpineClickHandler())->toContain('opacity');
});

it('notifies without crashing when the secret is not found', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token']),
        '*.vault.azure.net/secrets/*' => Http::response(['error' => ['code' => 'SecretNotFound']], 404),
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->callAction(TestAction::make('reveal')->table($credential))
        ->assertNotified();

    expect(AuditEvent::withoutGlobalScope(OrganizationScope::class)->where('credential_id', $credential->id)->count())->toBe(0);
});

it('notifies without crashing when retrieval fails', function () {
    $credential = credentialWithVaultConfig();

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['error' => 'invalid_client'], 401),
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->callAction(TestAction::make('reveal')->table($credential))
        ->assertNotified();

    expect(AuditEvent::withoutGlobalScope(OrganizationScope::class)->where('credential_id', $credential->id)->count())->toBe(0);
});

it('is absent when the credential\'s provider has no integration at all', function () {
    $credential = credentialWithVaultConfig();
    $credential->update(['secret_provider' => SecretProvider::HashicorpVault]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->assertActionHidden(TestAction::make('reveal')->table($credential));
});

it('is absent when the organization has no vault configured', function () {
    $credential = credentialWithVaultConfig(vaultUrl: null);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->assertActionHidden(TestAction::make('reveal')->table($credential));
});

it('reveals a Bitwarden secret value through the same action', function () {
    $organization = Organization::factory()->create(['bitwarden_access_token' => '0.access-token-value']);
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'secret_provider' => SecretProvider::Bitwarden,
        'secret_reference' => 'be8e0ad8-d545-4017-a55a-b02f014d4158',
    ]);

    Process::fake([
        '*bws*' => Process::result(output: json_encode(['value' => 'bitwarden-secret-value'])),
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->assertActionVisible(TestAction::make('reveal')->table($credential))
        ->callAction(TestAction::make('reveal')->table($credential))
        ->assertNotified();

    expect($credential->fresh()->last_retrieved_at)->not->toBeNull();
});

it('never appears in the credentials table', function () {
    $credential = credentialWithVaultConfig();

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $credential->environment,
        'pageClass' => ViewEnvironment::class,
    ])->assertDontSee('super-secret-password');
});
