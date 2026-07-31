<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

it('shows an Audits tab with the change history on the Customer view page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create(['name' => 'Original']);
    $customer->update(['name' => 'Updated']);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Audits');

    Livewire::test(AuditsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertSuccessful()
        ->assertSee($user->name)
        ->assertSee('updated');
});

it('shows an Audits tab with the change history on the Environment view page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
        'name' => 'Original',
    ]);
    $environment->update(['name' => 'Updated']);

    Livewire::test(ViewEnvironment::class, ['record' => $environment->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Audits');

    Livewire::test(AuditsRelationManager::class, [
        'ownerRecord' => $environment,
        'pageClass' => ViewEnvironment::class,
    ])
        ->assertSuccessful()
        ->assertSee($user->name)
        ->assertSee('updated');
});

it('does not leak another organization\'s audit history into the relation manager', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userB = User::factory()->for($orgB)->create();
    $this->actingAs($userB);
    $customerB = Customer::factory()->for($orgB)->create(['name' => 'Org B customer']);
    $customerB->update(['name' => 'Org B customer renamed']);

    $userA = User::factory()->for($orgA)->create();
    $this->actingAs($userA);
    $customerA = Customer::factory()->for($orgA)->create(['name' => 'Org A customer']);
    $customerA->update(['name' => 'Org A customer renamed']);

    Livewire::test(AuditsRelationManager::class, [
        'ownerRecord' => $customerA,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertSuccessful()
        ->assertDontSee('Org B customer renamed');
});
