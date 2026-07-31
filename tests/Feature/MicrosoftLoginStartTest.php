<?php

use App\Models\Organization;

it('shows an organization slug entry form', function () {
    $response = $this->get(route('auth.microsoft.start'));

    $response->assertOk();
    $response->assertSee('name="slug"', false);
});

it('loads the app stylesheet instead of rendering as bare unstyled HTML', function () {
    $response = $this->get(route('auth.microsoft.start'));

    $response->assertOk();
    $response->assertSee('.css', false);
});

it('shows an organization-not-found message for an unknown slug', function () {
    $response = $this->post(route('auth.microsoft.resolve'), ['slug' => 'no-such-org']);

    $response->assertOk();
    $response->assertSee('Organization not found');
});

it('shows a password-only message when the organization has no Entra config', function () {
    Organization::factory()->create(['slug' => 'acme-hvac']);

    $response = $this->post(route('auth.microsoft.resolve'), ['slug' => 'acme-hvac']);

    $response->assertOk();
    $response->assertSee('Microsoft sign-in not available');
});

it('redirects to the organization\'s own Entra app when one is configured', function () {
    Organization::factory()->create([
        'slug' => 'acme-hvac',
        'azure_client_id' => 'org-client-123',
        'azure_client_secret' => 'org-secret',
        'azure_tenant_id' => 'org-tenant-456',
    ]);

    $response = $this->post(route('auth.microsoft.resolve'), ['slug' => 'acme-hvac']);

    $response->assertRedirect();
    $location = $response->headers->get('Location');
    expect($location)->toContain('login.microsoftonline.com/org-tenant-456')
        ->and($location)->toContain('client_id=org-client-123');
});
