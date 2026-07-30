<?php

use App\Models\Organization;
use App\Models\User;

it('redirects guests away from the platform panel to its login page', function () {
    $response = $this->get('/platform');

    $response->assertRedirect('/platform/login');
});

it('denies a non-platform-owner access to the platform panel', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['is_platform_owner' => false]);

    $response = $this->actingAs($user)->get('/platform');

    $response->assertForbidden();
});

it('lets a platform owner access the platform panel and see all organizations', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->for($organization)->create(['is_platform_owner' => true]);

    $otherOrganization = Organization::factory()->create(['name' => 'Other Org']);

    $response = $this->actingAs($owner)->get('/platform/organizations');

    $response->assertOk();
    $response->assertSee('Other Org');
});

it('does not affect a platform owner\'s access to their own org admin panel', function () {
    $organization = Organization::factory()->create();
    $owner = User::factory()->for($organization)->create(['is_platform_owner' => true]);

    $response = $this->actingAs($owner)->get('/admin');

    $response->assertOk();
});
