<?php

use App\Enums\OrganizationRole;
use App\Filament\Pages\Auth\OrganizationRegister;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Livewire\Livewire;

it('creates a new organization and becomes its admin on signup', function () {
    Livewire::test(OrganizationRegister::class)
        ->fillForm([
            'organization_name' => 'Acme HVAC',
            'name' => 'Jane Doe',
            'email' => 'jane@acme-hvac.test',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScope(OrganizationScope::class)->where('email', 'jane@acme-hvac.test')->sole();
    $organization = Organization::query()->where('name', 'Acme HVAC')->sole();

    expect($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::PlatformAdministrator)
        ->and(auth()->id())->toBe($user->id);
});

it('creates its own separate organization even when another org already uses the same email domain', function () {
    $existingOrganization = Organization::factory()->create(['name' => 'Acme HVAC']);

    Livewire::test(OrganizationRegister::class)
        ->fillForm([
            'organization_name' => 'Second Company',
            'name' => 'Second Person',
            'email' => 'second@acme-hvac.test',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScope(OrganizationScope::class)->where('email', 'second@acme-hvac.test')->sole();
    $newOrganization = Organization::query()->where('name', 'Second Company')->sole();

    expect($user->organization_id)->toBe($newOrganization->id)
        ->and($user->organization_id)->not->toBe($existingOrganization->id)
        ->and($user->role)->toBe(OrganizationRole::PlatformAdministrator);
});
