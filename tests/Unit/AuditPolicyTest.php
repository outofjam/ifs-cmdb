<?php

use App\Enums\EnvironmentType;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\EnvironmentPolicy;

// tapp/filament-auditing's AuditsRelationManager gates the "Audits" tab via
// Filament::auth()->user()->can('audit', $ownerRecord), and its restore
// action via can('restoreAudit', ...). These policies are the only
// authorization layer these two abilities have -- exercise them directly.
dataset('auditable resource policies', [
    'Customer' => [CustomerPolicy::class, fn (Organization $organization) => Customer::factory()->for($organization)->create()],
    'Environment' => [EnvironmentPolicy::class, function (Organization $organization) {
        $customer = Customer::factory()->for($organization)->create();

        return Environment::factory()->for($organization)->create([
            'customer_id' => $customer->id,
            'type' => EnvironmentType::Uat,
        ]);
    }],
]);

it('allows a user to view audit history for a record in their own organization', function (string $policyClass, Closure $makeRecord) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $record = $makeRecord($organization);

    expect((new $policyClass)->audit($user, $record))->toBeTrue();
})->with('auditable resource policies');

it('denies a user from viewing audit history for another organization\'s record', function (string $policyClass, Closure $makeRecord) {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->for($orgA)->create();
    $record = $makeRecord($orgB);

    expect((new $policyClass)->audit($user, $record))->toBeFalse();
})->with('auditable resource policies');

it('denies restoring a record from its audit history, not requested yet', function (string $policyClass, Closure $makeRecord) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $record = $makeRecord($organization);

    expect((new $policyClass)->restoreAudit($user, $record))->toBeFalse();
})->with('auditable resource policies');
