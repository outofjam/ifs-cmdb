<?php

use App\Enums\EnvironmentType;
use App\Enums\VerificationStatus;
use App\Models\Audit;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;

// Reveal bumps last_retrieved_at/last_retrieved_by on every click, and
// that's already captured by the dedicated AuditEvent security log (see
// CredentialsRelationManager::revealAction()). Without exclusion, the
// general Audit trail also logs every one of these as a near-content-free
// "Updated" entry, drowning out meaningful changes (name, secret_reference,
// purpose, etc.) -- see the "Audit history for a credential is ugly and
// useless" note in CLAUDE.md.
it('does not create a general audit entry when only reveal-tracking fields change', function () {
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
    ]);

    $before = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', Credential::class)
        ->where('auditable_id', $credential->id)
        ->count();

    $credential->update([
        'last_retrieved_at' => now(),
        'last_retrieved_by' => $user->id,
    ]);

    $after = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', Credential::class)
        ->where('auditable_id', $credential->id)
        ->count();

    expect($after)->toBe($before);
});

it('excludes the verification timestamp but keeps the status change', function () {
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
    ]);

    $credential->update([
        'verification_status' => VerificationStatus::Verified,
        'last_verified_at' => now(),
    ]);

    $audit = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', Credential::class)
        ->where('auditable_id', $credential->id)
        ->where('event', 'updated')
        ->sole();

    expect($audit->new_values)->toHaveKey('verification_status')
        ->and($audit->new_values)->not->toHaveKey('last_verified_at');
});

it('still audits meaningful field changes normally', function () {
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
        'name' => 'Original name',
    ]);

    $credential->update(['name' => 'Renamed credential']);

    $audit = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', Credential::class)
        ->where('auditable_id', $credential->id)
        ->where('event', 'updated')
        ->sole();

    expect($audit->new_values)->toBe(['name' => 'Renamed credential']);
});
