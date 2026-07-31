<?php

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Filament\Resources\Credentials\Pages\CreateCredential;
use App\Filament\Resources\Credentials\Pages\ListCredentials;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lists credentials belonging to the authenticated user\'s organization', function () {
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

    Livewire::test(ListCredentials::class)
        ->assertCanSeeTableRecords([$credential])
        ->assertSee('IFS Administrator');
});

it('does not list another organization\'s credentials', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->for($orgA)->create();
    $this->actingAs($user);

    $customerB = Customer::factory()->for($orgB)->create();
    $environmentB = Environment::factory()->for($orgB)->create([
        'customer_id' => $customerB->id,
        'type' => EnvironmentType::Uat,
    ]);
    $otherCredential = Credential::factory()->for($orgB)->create([
        'environment_id' => $environmentB->id,
    ]);

    Livewire::test(ListCredentials::class)
        ->assertCanNotSeeTableRecords([$otherCredential]);
});

it('does not offer a production environment in the environment picker', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'name' => 'Acme Production',
        'type' => EnvironmentType::Production,
    ]);
    Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'name' => 'Acme UAT',
        'type' => EnvironmentType::Uat,
    ]);

    Livewire::test(CreateCredential::class)
        ->assertSee('Acme UAT')
        ->assertDontSee('Acme Production');
});

it('creates a credential with a provider and reference through the form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $owner = User::factory()->for($organization)->create();
    $this->actingAs($user);

    Livewire::test(CreateCredential::class)
        ->fillForm([
            'environment_id' => $environment->id,
            'name' => 'IFS Administrator',
            'username' => 'ifsadmin',
            'secret_provider' => SecretProvider::AzureKeyVault->value,
            'secret_reference' => 'acme-uat-ifs-admin',
            'owner_id' => $owner->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $credential = Credential::query()->where('name', 'IFS Administrator')->sole();

    expect($credential->organization_id)->toBe($organization->id)
        ->and($credential->environment_id)->toBe($environment->id)
        ->and($credential->secret_provider)->toBe(SecretProvider::AzureKeyVault)
        ->and($credential->secret_reference)->toBe('acme-uat-ifs-admin')
        ->and($credential->owner_id)->toBe($owner->id);
});
