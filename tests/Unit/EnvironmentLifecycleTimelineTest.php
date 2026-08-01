<?php

use App\Enums\EnvironmentType;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

// docs/plan.md §12 "Environment Lifecycle Management" names six event
// types (creation, refresh, clone, upgrade, deployment, configuration
// change), but only three are derivable from data this app actually
// tracks -- a refresh/clone/deployment doesn't change any field on
// Environment, so nothing in the audit trail signals it. Scoped to what's
// derivable: created, upgraded (ifs_release/build_number changed), and
// configuration_changed (anything else).
it('categorizes creation as a lifecycle event', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'ifs_release' => '24R1',
    ]);

    $events = $environment->lifecycleTimeline();

    expect($events)->toHaveCount(1)
        ->and($events->first()['type'])->toBe('created');
});

it('categorizes an ifs_release change as an upgrade', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'ifs_release' => '24R1',
    ]);
    $environment->update(['ifs_release' => '24R2']);

    $events = $environment->lifecycleTimeline();

    expect($events)->toHaveCount(2);

    $upgrade = $events->firstWhere('type', 'upgraded');

    expect($upgrade)->not->toBeNull()
        ->and($upgrade['detail'])->toBe('24R1 → 24R2');
});

it('categorizes a build_number-only change as an upgrade too', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'build_number' => '1000',
    ]);
    $environment->update(['build_number' => '1001']);

    $upgrade = $environment->lifecycleTimeline()->firstWhere('type', 'upgraded');

    expect($upgrade)->not->toBeNull()
        ->and($upgrade['detail'])->toBe('Build 1000 → 1001');
});

it('categorizes any other field change as a configuration change', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'name' => 'Original',
    ]);
    $environment->update(['name' => 'Renamed']);

    $events = $environment->lifecycleTimeline();
    $change = $events->firstWhere('type', 'configuration_changed');

    expect($change)->not->toBeNull()
        ->and($change['detail'])->toContain('Name');
});

it('orders events most recent first', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'ifs_release' => '24R1',
    ]);
    $environment->update(['ifs_release' => '24R2']);
    $environment->update(['ifs_release' => '25R1']);

    $events = $environment->lifecycleTimeline();

    expect($events->pluck('type')->all())->toBe(['upgraded', 'upgraded', 'created']);
});
