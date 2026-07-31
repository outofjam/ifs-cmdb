<?php

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;

it('can create a customer with an owner and notes', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->for($organization)->create();

    // owner()/organization() traverse OrganizationScope-guarded queries, which
    // fail closed with no authenticated user -- matches real panel usage.
    $this->actingAs($owner);

    $customer = Customer::factory()->for($organization)->create([
        'name' => 'Acme HVAC',
        'owner_id' => $owner->id,
        'notes' => 'Key account, renews in March.',
    ]);

    expect($customer->name)->toBe('Acme HVAC')
        ->and($customer->owner->is($owner))->toBeTrue()
        ->and($customer->notes)->toBe('Key account, renews in March.')
        ->and($customer->organization->is($organization))->toBeTrue();
});

// Cross-org isolation is covered for all tenant models in
// tests/Unit/OrganizationScopeIsolationTest.php.
