<?php

use App\Models\Organization;
use App\Models\User;

it('defaults new users to not being a platform owner', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    expect($user->fresh()->is_platform_owner)->toBeFalse();
});

it('can flag a user as a platform owner', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['is_platform_owner' => true]);

    expect($user->fresh()->is_platform_owner)->toBeTrue();
});
