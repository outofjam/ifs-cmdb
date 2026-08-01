<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\EnvironmentsRelationManager;
use App\Filament\Resources\Environments\EnvironmentResource;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Actions\ViewAction;
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

it('links View to the environment\'s own page instead of opening a modal', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertActionHasUrl(
            TestAction::make(ViewAction::class)->table($environment),
            EnvironmentResource::getUrl('view', ['record' => $environment]),
        );
});

it('links Edit to the environment\'s own page instead of opening a modal', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertActionHasUrl(
            TestAction::make(EditAction::class)->table($environment),
            EnvironmentResource::getUrl('edit', ['record' => $environment]),
        );
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

it('shows the number of credentials belonging to each environment', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    Credential::factory()->for($organization)->count(2)->create([
        'environment_id' => $environment->id,
    ]);

    Livewire::test(EnvironmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertTableColumnStateSet('credentials_count', 2, record: $environment);
});
