<?php

use App\Models\Organization;
use App\Models\User;

it('only returns users belonging to the authenticated user\'s organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->for($orgA)->create();
    User::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(User::query()->pluck('id'))->toEqual(collect([$userA->id]));
});

it('cannot read another organization\'s user by guessing its id', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->for($orgA)->create();
    $userB = User::factory()->for($orgB)->create();

    $this->actingAs($userA);

    expect(User::find($userB->id))->toBeNull();
});

it('returns no rows when there is no authenticated user', function () {
    Organization::factory()->create();
    User::factory()->create();

    expect(User::query()->count())->toBe(0);
});
