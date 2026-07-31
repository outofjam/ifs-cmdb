<?php

use App\Enums\EnvironmentType;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

it('can create an environment with a customer, owner, type, and notes', function () {
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
        'notes' => 'Refreshed monthly from production.',
    ]);

    expect($environment->name)->toBe('Acme UAT')
        ->and($environment->type)->toBe(EnvironmentType::Uat)
        ->and($environment->customer->is($customer))->toBeTrue()
        ->and($environment->owner->is($owner))->toBeTrue()
        ->and($environment->organization->is($organization))->toBeTrue()
        ->and($environment->url)->toBe('https://acme-uat.ifscloud.com')
        ->and($environment->ifs_release)->toBe('24R2')
        ->and($environment->build_number)->toBe('4821')
        ->and($environment->notes)->toBe('Refreshed monthly from production.');
});
