<?php

use App\Enums\OrganizationRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lists users belonging to the authenticated admin\'s organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create([
        'role' => OrganizationRole::PlatformAdministrator,
        'name' => 'Ada Admin',
    ]);
    $this->actingAs($admin);

    $viewer = User::factory()->for($organization)->create(['name' => 'Vic Viewer']);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$admin, $viewer])
        ->assertSee('Vic Viewer');
});

it('does not list another organization\'s users', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $admin = User::factory()->for($orgA)->create(['role' => OrganizationRole::PlatformAdministrator]);
    $this->actingAs($admin);

    $otherUser = User::factory()->for($orgB)->create();

    Livewire::test(ListUsers::class)
        ->assertCanNotSeeTableRecords([$otherUser]);
});

it('is only accessible to a platform administrator', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);
    $viewer = User::factory()->for($organization)->create(['role' => OrganizationRole::Viewer]);

    $this->actingAs($admin);
    expect(UserResource::canViewAny())->toBeTrue();

    $this->actingAs($viewer);
    expect(UserResource::canViewAny())->toBeFalse();
});

it('lets an admin change another user\'s role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);
    $this->actingAs($admin);

    $viewer = User::factory()->for($organization)->create(['role' => OrganizationRole::Viewer]);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('changeRole')->table($viewer), [
            'role' => OrganizationRole::DeliveryManager->value,
        ])
        ->assertHasNoActionErrors();

    expect($viewer->fresh()->role)->toBe(OrganizationRole::DeliveryManager);
});

it('prevents an admin from changing their own role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->for($organization)->create(['role' => OrganizationRole::PlatformAdministrator]);
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('changeRole')->table($admin));
});
