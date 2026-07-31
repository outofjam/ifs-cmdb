<?php

use App\Enums\OrganizationRole;
use App\Filament\Pages\BitwardenSettings;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lets an org admin save their Bitwarden access token', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);

    Livewire::actingAs($admin)
        ->test(BitwardenSettings::class)
        ->fillForm([
            'bitwarden_access_token' => '0.access-token-value',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->fresh()->bitwarden_access_token)->toBe('0.access-token-value');
});

it('denies access to a non-admin role', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->for($organization)->create(['role' => OrganizationRole::Viewer]);

    $this->actingAs($viewer);

    expect(BitwardenSettings::canAccess())->toBeFalse();
});

it('allows access to an org admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);

    $this->actingAs($admin);

    expect(BitwardenSettings::canAccess())->toBeTrue();
});
