<?php

use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
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
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-consulting.test']);

    $user = User::withoutGlobalScope(OrganizationScope::class)
        ->create([
            'organization_id' => $organization->id,
            'name' => 'Sarah Smith',
            'email' => 'sarah@acme-consulting.test',
            'password' => str()->random(40),
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

it('shows a contact message instead of logging in when the domain is not approved', function () {
    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-999',
        'email' => 'someone@unapproved-company.test',
        'name' => 'Someone',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertOk();
    $response->assertSee('mailto:ish@outofjam.com', false);
    $this->assertGuest();
});
