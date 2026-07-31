<?php

use App\Enums\EnvironmentType;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

dataset('tenant models', [
    'Customer' => [fn (Organization $organization) => Customer::factory()->for($organization)->create()],
    'Environment' => [fn (Organization $organization) => Environment::factory()->for($organization)->create([
        'customer_id' => Customer::factory()->for($organization)->create()->id,
    ])],
    'Credential' => [fn (Organization $organization) => Credential::factory()->for($organization)->create([
        'environment_id' => Environment::factory()->for($organization)->create([
            'customer_id' => Customer::factory()->for($organization)->create()->id,
            'type' => EnvironmentType::Uat,
        ])->id,
    ])],
]);

it('only returns records belonging to the authenticated user\'s organization', function (Closure $makeRecord) {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $recordA = $makeRecord($orgA);
    $makeRecord($orgB);

    $this->actingAs($userA);

    expect($recordA::query()->pluck('id'))->toEqual(collect([$recordA->id]));
})->with('tenant models');

it('cannot read another organization\'s record by guessing its id', function (Closure $makeRecord) {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $recordB = $makeRecord($orgB);

    $this->actingAs($userA);

    expect($recordB::find($recordB->id))->toBeNull();
})->with('tenant models');

it('returns no records when there is no authenticated user', function (Closure $makeRecord) {
    $organization = Organization::factory()->create();
    $record = $makeRecord($organization);

    expect($record::query()->count())->toBe(0);
})->with('tenant models');
