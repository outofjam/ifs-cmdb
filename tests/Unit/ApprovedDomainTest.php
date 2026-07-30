<?php

use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\User;

it('can create an approved domain for an organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $approvedDomain = ApprovedDomain::factory()->for($organization)->create([
        'domain' => 'acme.com',
    ]);

    expect($approvedDomain->domain)->toBe('acme.com')
        ->and($approvedDomain->organization->is($organization))->toBeTrue();
});

it('only returns approved domains belonging to the authenticated user\'s organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $domainA = ApprovedDomain::factory()->for($orgA)->create();
    ApprovedDomain::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(ApprovedDomain::query()->pluck('id'))->toEqual(collect([$domainA->id]));
});

it('cannot read another organization\'s approved domain by guessing its id', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();

    $domainB = ApprovedDomain::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(ApprovedDomain::find($domainB->id))->toBeNull();
});

it('returns no approved domains when there is no authenticated user', function () {
    $organization = Organization::factory()->create();
    ApprovedDomain::factory()->for($organization)->create();

    expect(ApprovedDomain::query()->count())->toBe(0);
});
