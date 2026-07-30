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

it('only returns customers belonging to the authenticated user\'s organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $customerA = Customer::factory()->for($orgA)->create();
    Customer::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(Customer::query()->pluck('id'))->toEqual(collect([$customerA->id]));
});

it('cannot read another organization\'s customer by guessing its id', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $customerB = Customer::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(Customer::find($customerB->id))->toBeNull();
});

it('returns no customers when there is no authenticated user', function () {
    $organization = Organization::factory()->create();
    Customer::factory()->for($organization)->create();

    expect(Customer::query()->count())->toBe(0);
});
