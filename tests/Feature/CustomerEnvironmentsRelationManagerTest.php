<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\EnvironmentsRelationManager;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('lists environments belonging to the customer', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'name' => 'Acme UAT',
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertCanSeeTableRecords([$environment])
        ->assertSee('Acme UAT');
});

it('does not list another customer\'s environments', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customerA = Customer::factory()->for($organization)->create();
    $customerB = Customer::factory()->for($organization)->create();
    $otherEnvironment = Environment::factory()->for($organization)->create([
        'customer_id' => $customerB->id,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customerA,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertCanNotSeeTableRecords([$otherEnvironment]);
});

it('creates an environment for this customer without needing to pick a customer', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();

    // Relation managers are read-only on ViewRecord pages by Filament
    // convention -- mutations happen from the Edit page.
    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'name' => 'Acme UAT',
            'type' => EnvironmentType::Uat->value,
        ])
        ->assertHasNoActionErrors();

    $environment = Environment::query()->where('name', 'Acme UAT')->sole();

    expect($environment->organization_id)->toBe($organization->id)
        ->and($environment->customer_id)->toBe($customer->id);
});

it('edits an environment through the relation manager', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'name' => 'Original name',
        'type' => EnvironmentType::Uat,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->callAction(TestAction::make(EditAction::class)->table($environment), [
            'name' => 'Renamed environment',
        ])
        ->assertHasNoActionErrors();

    expect($environment->fresh()->name)->toBe('Renamed environment')
        ->and($environment->fresh()->customer_id)->toBe($customer->id);
});

it('deletes an environment through the relation manager', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->callAction(TestAction::make(DeleteAction::class)->table($environment));

    expect(Environment::query()->find($environment->id))->toBeNull();
});
