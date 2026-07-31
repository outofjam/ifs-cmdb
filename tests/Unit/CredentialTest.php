<?php

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Exceptions\CredentialTargetsProductionEnvironmentException;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

it('can create a credential with an environment, provider, and owner', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $owner = User::factory()->for($organization)->create();

    // organization()/environment()/owner() traverse OrganizationScope-guarded
    // queries, which fail closed with no authenticated user.
    $this->actingAs($owner);

    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'IFS Administrator',
        'purpose' => 'UAT admin access for the implementation team.',
        'username' => 'ifsadmin',
        'secret_provider' => SecretProvider::AzureKeyVault,
        'secret_reference' => 'acme-uat-ifs-admin',
        'expiration_date' => '2027-01-01',
        'owner_id' => $owner->id,
    ]);

    expect($credential->secret_provider)->toBeInstanceOf(SecretProvider::class)
        ->and($credential->name)->toBe('IFS Administrator')
        ->and($credential->purpose)->toBe('UAT admin access for the implementation team.')
        ->and($credential->username)->toBe('ifsadmin')
        ->and($credential->secret_provider)->toBe(SecretProvider::AzureKeyVault)
        ->and($credential->secret_reference)->toBe('acme-uat-ifs-admin')
        ->and($credential->expiration_date->toDateString())->toBe('2027-01-01')
        ->and($credential->environment->is($environment))->toBeTrue()
        ->and($credential->owner->is($owner))->toBeTrue()
        ->and($credential->organization->is($organization))->toBeTrue();
});

it('refuses to attach a credential to a production environment', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Production,
    ]);

    expect(fn () => Credential::factory()->for($organization)->create(['environment_id' => $environment->id]))
        ->toThrow(CredentialTargetsProductionEnvironmentException::class);
});

it('allows a credential against any non-production environment type', function (EnvironmentType $type) {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => $type,
    ]);

    $credential = Credential::factory()->for($organization)->create(['environment_id' => $environment->id]);

    expect($credential->exists)->toBeTrue();
})->with([
    EnvironmentType::Uat,
    EnvironmentType::Test,
    EnvironmentType::Development,
    EnvironmentType::Training,
    EnvironmentType::Demo,
    EnvironmentType::Integration,
]);
