<?php

use App\Enums\EnvironmentType;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

it('can create an environment with a customer, owner, type, and knowledge fields', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $owner = User::factory()->for($organization)->create();

    // organization()/customer()/owner() traverse OrganizationScope-guarded
    // queries, which fail closed with no authenticated user.
    $this->actingAs($owner);

    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'owner_id' => $owner->id,
        'name' => 'Acme UAT',
        'type' => EnvironmentType::Uat,
        'url' => 'https://acme-uat.ifscloud.com',
        'ifs_release' => '24R2',
        'build_number' => '4821',
        'purpose' => 'Used for regression testing before each release.',
        'configuration_notes' => 'Mirrors production config except outbound email is disabled.',
        'known_issues' => 'Scheduled jobs occasionally double-fire after a refresh.',
        'troubleshooting_notes' => 'Restart the app server if login hangs.',
        'customer_procedures' => 'Customer requires a Slack heads-up before any refresh.',
    ]);

    expect($environment->name)->toBe('Acme UAT')
        ->and($environment->type)->toBe(EnvironmentType::Uat)
        ->and($environment->customer->is($customer))->toBeTrue()
        ->and($environment->owner->is($owner))->toBeTrue()
        ->and($environment->organization->is($organization))->toBeTrue()
        ->and($environment->url)->toBe('https://acme-uat.ifscloud.com')
        ->and($environment->ifs_release)->toBe('24R2')
        ->and($environment->build_number)->toBe('4821')
        ->and($environment->purpose)->toBe('Used for regression testing before each release.')
        ->and($environment->configuration_notes)->toBe('Mirrors production config except outbound email is disabled.')
        ->and($environment->known_issues)->toBe('Scheduled jobs occasionally double-fire after a refresh.')
        ->and($environment->troubleshooting_notes)->toBe('Restart the app server if login hangs.')
        ->and($environment->customer_procedures)->toBe('Customer requires a Slack heads-up before any refresh.');
});

it('has many credentials', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $owner = User::factory()->for($organization)->create();
    $this->actingAs($owner);

    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create(['environment_id' => $environment->id]);

    expect($environment->credentials)->toHaveCount(1)
        ->and($environment->credentials->first()->is($credential))->toBeTrue();
});
