<?php

use App\Models\Organization;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects to the Microsoft OAuth consent screen', function () {
    $response = $this->get(route('auth.microsoft.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login.microsoftonline.com');
});

it('logs in an existing user on successful callback', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'sarah@acme-consulting.test',
    ]);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'sarah@acme-consulting.test',
        'name' => 'Sarah Smith',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});
