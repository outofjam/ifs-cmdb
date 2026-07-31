<?php

use App\Enums\OrganizationRole;
use App\Filament\Pages\EntraSettings;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lets an org admin save their Entra app config', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);

    Livewire::actingAs($admin)
        ->test(EntraSettings::class)
        ->fillForm([
            'azure_client_id' => 'client-123',
            'azure_client_secret' => 'super-secret-value',
            'azure_tenant_id' => 'tenant-456',
            'azure_key_vault_url' => 'https://acme-vault.vault.azure.net',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $organization->fresh();

    expect($fresh->azure_client_id)->toBe('client-123')
        ->and($fresh->azure_client_secret)->toBe('super-secret-value')
        ->and($fresh->azure_tenant_id)->toBe('tenant-456')
        ->and($fresh->azure_key_vault_url)->toBe('https://acme-vault.vault.azure.net');
});

it('denies access to a non-admin role', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->for($organization)->create(['role' => OrganizationRole::Viewer]);

    $this->actingAs($viewer);

    expect(EntraSettings::canAccess())->toBeFalse();
});

it('allows access to an org admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);

    $this->actingAs($admin);

    expect(EntraSettings::canAccess())->toBeTrue();
});
