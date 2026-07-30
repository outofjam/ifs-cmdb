<?php

use App\Enums\OrganizationRole;
use App\Filament\Pages\Auth\OrganizationRegister;
use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Livewire\Livewire;

it('creates a new organization and becomes its admin on first signup from a domain', function () {
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
    $approvedDomain = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->where('domain', 'acme-hvac.test')->sole();

    expect($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::PlatformAdministrator)
        ->and($approvedDomain->organization_id)->toBe($organization->id)
        ->and(auth()->id())->toBe($user->id);
});

it('joins the existing organization as a viewer when the domain is already registered', function () {
    $organization = Organization::factory()->create(['name' => 'Acme HVAC']);
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-hvac.test']);

    Livewire::test(OrganizationRegister::class)
        ->fillForm([
            'organization_name' => 'Ignored — domain already has an org',
            'name' => 'Second Person',
            'email' => 'second@acme-hvac.test',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::withoutGlobalScope(OrganizationScope::class)->where('email', 'second@acme-hvac.test')->sole();

    expect($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::Viewer)
        ->and(Organization::query()->where('name', 'Acme HVAC')->count())->toBe(1)
        ->and(ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->where('domain', 'acme-hvac.test')->count())->toBe(1);
});
