<?php

use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Azure\Provider as AzureProvider;

it('shows an email entry form', function () {
    $response = $this->get(route('auth.microsoft.start'));

    $response->assertOk();
    $response->assertSee('name="email"', false);
});

it('redirects to the org\'s own Entra app when one is configured', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-hvac.test']);

    $response = $this->post(route('auth.microsoft.resolve'), ['email' => 'jane@acme-hvac.test']);

    $response->assertRedirect();
    $location = $response->headers->get('Location');
    expect($location)->toContain('login.microsoftonline.com/org-tenant-456')
        ->and($location)->toContain('client_id=org-client-123');
});

it('falls back to the shared platform app when the org has no Entra config of its own', function () {
    $organization = Organization::factory()->create();
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-hvac.test']);

    $response = $this->post(route('auth.microsoft.resolve'), ['email' => 'jane@acme-hvac.test']);

    $response->assertRedirect();
    $location = $response->headers->get('Location');
    expect($location)->toContain('login.microsoftonline.com/'.config('services.azure.tenant'))
        ->and($location)->toContain('client_id='.config('services.azure.client_id'));
});

it('shows a contact message for an unapproved domain instead of redirecting', function () {
    $response = $this->post(route('auth.microsoft.resolve'), ['email' => 'someone@unapproved-company.test']);

    $response->assertOk();
    $response->assertSee('mailto:ish@outofjam.com', false);
});

it('completes login through the org\'s own Entra app end to end', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-hvac.test']);

    $this->post(route('auth.microsoft.resolve'), ['email' => 'jane@acme-hvac.test']);

    $socialiteUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'jane@acme-hvac.test',
        'name' => 'Jane Doe',
    ]);

    $fakeProvider = Mockery::mock(AzureProvider::class);
    $fakeProvider->shouldReceive('setConfig')->andReturnSelf();
    $fakeProvider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('buildProvider')
        ->with(AzureProvider::class, Mockery::type('array'))
        ->andReturn($fakeProvider);

    $response = $this->get(route('auth.microsoft.callback'));

    $response->assertRedirect('/admin');
    $user = User::withoutGlobalScope(OrganizationScope::class)->where('email', 'jane@acme-hvac.test')->sole();
    expect($user->organization_id)->toBe($organization->id);
});
