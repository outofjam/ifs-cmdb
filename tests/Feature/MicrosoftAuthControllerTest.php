<?php

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Azure\Provider as AzureProvider;

it('logs in an existing user when the callback\'s tenant ID matches the resolved organization', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);

    $user = User::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Sarah Smith',
        'email' => 'sarah@acme-consulting.test',
        'password' => str()->random(40),
    ]);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'sarah@acme-consulting.test',
        'name' => 'Sarah Smith',
    ])->setToken(fakeJwt(['tid' => 'org-tenant-456']));

    $fakeProvider = Mockery::mock(AzureProvider::class);
    $fakeProvider->shouldReceive('setConfig')->andReturnSelf();
    $fakeProvider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('buildProvider')
        ->with(AzureProvider::class, Mockery::type('array'))
        ->andReturn($fakeProvider);

    $this->withSession(['microsoft_login_organization_id' => $organization->id]);
    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

it('creates a new viewer under the resolved organization on first login', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'jane@acme-hvac.test',
        'name' => 'Jane Doe',
    ])->setToken(fakeJwt(['tid' => 'org-tenant-456']));

    $fakeProvider = Mockery::mock(AzureProvider::class);
    $fakeProvider->shouldReceive('setConfig')->andReturnSelf();
    $fakeProvider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('buildProvider')
        ->with(AzureProvider::class, Mockery::type('array'))
        ->andReturn($fakeProvider);

    $this->withSession(['microsoft_login_organization_id' => $organization->id]);
    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect('/admin');
    $user = User::withoutGlobalScope(OrganizationScope::class)->where('email', 'jane@acme-hvac.test')->sole();
    expect($user->organization_id)->toBe($organization->id);
});

it('rejects the login when the callback\'s tenant ID does not match the resolved organization', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-999',
        'email' => 'attacker@some-other-tenant.test',
        'name' => 'Attacker',
    ])->setToken(fakeJwt(['tid' => 'attacker-tenant-999']));

    $fakeProvider = Mockery::mock(AzureProvider::class);
    $fakeProvider->shouldReceive('setConfig')->andReturnSelf();
    $fakeProvider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('buildProvider')
        ->with(AzureProvider::class, Mockery::type('array'))
        ->andReturn($fakeProvider);

    $this->withSession(['microsoft_login_organization_id' => $organization->id]);
    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertOk();
    $response->assertSee("couldn't verify your account", false);
    $this->assertGuest();
    expect(User::withoutGlobalScope(OrganizationScope::class)->where('email', 'attacker@some-other-tenant.test')->count())->toBe(0);
});

it('redirects to the start page when no organization was resolved in session', function () {
    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect(route('auth.microsoft.start'));
});
