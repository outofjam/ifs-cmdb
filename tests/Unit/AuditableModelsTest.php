<?php

use App\Enums\EnvironmentType;
use App\Models\Audit;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;

// Audit rows are created by laravel-auditing's model observer, not via a
// factory, so this doesn't fit the Model::factory()->for($org)->create()
// shape the shared OrganizationScopeIsolationTest dataset assumes -- each
// closure here returns [model, attributesToUpdate, attributeToCheck]
// instead.
dataset('auditable models', [
    'Customer' => [function (Organization $organization) {
        $model = Customer::factory()->for($organization)->create(['name' => 'Original']);

        return [$model, ['name' => 'Updated'], 'name'];
    }],
    'Environment' => [function (Organization $organization) {
        $customer = Customer::factory()->for($organization)->create();
        $model = Environment::factory()->for($organization)->create([
            'customer_id' => $customer->id,
            'type' => EnvironmentType::Uat,
            'name' => 'Original',
        ]);

        return [$model, ['name' => 'Updated'], 'name'];
    }],
    'Credential' => [function (Organization $organization) {
        $customer = Customer::factory()->for($organization)->create();
        $environment = Environment::factory()->for($organization)->create([
            'customer_id' => $customer->id,
            'type' => EnvironmentType::Uat,
        ]);
        $model = Credential::factory()->for($organization)->create([
            'environment_id' => $environment->id,
            'name' => 'Original',
        ]);

        return [$model, ['name' => 'Updated'], 'name'];
    }],
]);

it('creates an audit record when created', function (Closure $makeModel) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    [$model] = $makeModel($organization);

    $audit = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', $model::class)
        ->where('auditable_id', $model->id)
        ->where('event', 'created')
        ->sole();

    expect($audit->organization_id)->toBe($organization->id)
        ->and($audit->user_id)->toBe($user->id);
})->with('auditable models');

it('creates an audit record with old and new values when updated', function (Closure $makeModel) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    [$model, $updates, $attribute] = $makeModel($organization);
    $originalValue = $model->{$attribute};
    $model->update($updates);

    $audit = Audit::withoutGlobalScope(OrganizationScope::class)
        ->where('auditable_type', $model::class)
        ->where('auditable_id', $model->id)
        ->where('event', 'updated')
        ->sole();

    expect($audit->old_values[$attribute])->toBe($originalValue)
        ->and($audit->new_values[$attribute])->toBe($updates[$attribute]);
})->with('auditable models');

it('scopes audits to the authenticated user\'s organization like every other tenant table', function (Closure $makeModel) {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $this->actingAs($userA);
    [$modelA] = $makeModel($orgA);

    $userB = User::factory()->for($orgB)->create();
    $this->actingAs($userB);
    $makeModel($orgB);

    $this->actingAs($userA);

    $visibleAuditableIds = Audit::query()->pluck('auditable_id');

    expect($visibleAuditableIds)->toContain($modelA->id)
        ->and(Audit::query()->where('organization_id', $orgB->id)->count())->toBe(0);
})->with('auditable models');
