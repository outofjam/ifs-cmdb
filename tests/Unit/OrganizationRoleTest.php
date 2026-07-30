<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

it('casts the role column to the OrganizationRole enum', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => OrganizationRole::Consultant,
    ]);

    expect($user->fresh()->role)->toBe(OrganizationRole::Consultant);
});

it('defaults new users to the Viewer role', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    expect($user->fresh()->role)->toBe(OrganizationRole::Viewer);
});

it('knows when a user is a platform administrator', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create([
        'role' => OrganizationRole::PlatformAdministrator,
    ]);
    $consultant = User::factory()->for($organization)->create([
        'role' => OrganizationRole::Consultant,
    ]);

    expect($admin->isPlatformAdministrator())->toBeTrue()
        ->and($consultant->isPlatformAdministrator())->toBeFalse();
});
