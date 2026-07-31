<?php

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Filament\Resources\Environments\Pages\EditEnvironment;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
use App\Filament\Resources\Environments\RelationManagers\CredentialsRelationManager;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lists credentials belonging to the environment', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'IFS Administrator',
    ]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->assertCanSeeTableRecords([$credential])
        ->assertSee('IFS Administrator');
});

it('does not list another environment\'s credentials', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environmentA = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $environmentB = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $otherCredential = Credential::factory()->for($organization)->create(['environment_id' => $environmentB->id]);

    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $environmentA,
        'pageClass' => ViewEnvironment::class,
    ])
        ->assertCanNotSeeTableRecords([$otherCredential]);
});

it('is hidden entirely for a production environment', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Production,
    ]);

    expect(CredentialsRelationManager::canViewForRecord($environment, ViewEnvironment::class))->toBeFalse();
});

it('is visible for a non-production environment', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    expect(CredentialsRelationManager::canViewForRecord($environment, ViewEnvironment::class))->toBeTrue();
});

it('creates a credential attached to the environment through the form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $owner = User::factory()->for($organization)->create();
    $this->actingAs($user);

    // Relation managers are read-only on ViewRecord pages by Filament
    // convention -- mutations happen from the Edit page, same as the
    // separate row-level EditAction.
    Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $environment,
        'pageClass' => EditEnvironment::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'name' => 'IFS Administrator',
            'username' => 'ifsadmin',
            'secret_provider' => SecretProvider::AzureKeyVault->value,
            'secret_reference' => 'acme-uat-ifs-admin',
            'owner_id' => $owner->id,
        ])
        ->assertHasNoActionErrors();

    $credential = Credential::query()->where('name', 'IFS Administrator')->sole();

    expect($credential->organization_id)->toBe($organization->id)
        ->and($credential->environment_id)->toBe($environment->id)
        ->and($credential->secret_provider)->toBe(SecretProvider::AzureKeyVault)
        ->and($credential->owner_id)->toBe($owner->id);
});
