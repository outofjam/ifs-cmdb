<?php

use App\Models\Organization;
use App\Models\User;

it('redirects guests away from the admin panel to the login page', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('shows a Microsoft sign-in link and no password field on the login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee(route('auth.microsoft.redirect'), false);
    $response->assertDontSee('name="password"', false);
});

it('lets an authenticated user reach the admin dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});
