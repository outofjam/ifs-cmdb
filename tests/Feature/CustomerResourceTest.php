<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lists customers belonging to the authenticated user\'s organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create(['name' => 'Acme HVAC']);

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertSee('Acme HVAC');
});

it('does not list another organization\'s customers', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->for($orgA)->create();
    $this->actingAs($user);

    $otherCustomer = Customer::factory()->for($orgB)->create();

    Livewire::test(ListCustomers::class)
        ->assertCanNotSeeTableRecords([$otherCustomer]);
});

it('creates a customer with an owner and notes through the form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $owner = User::factory()->for($organization)->create();
    $this->actingAs($user);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Brinks',
            'owner_id' => $owner->id,
            'notes' => 'Referred by Acme.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::query()->where('name', 'Brinks')->sole();

    expect($customer->organization_id)->toBe($organization->id)
        ->and($customer->owner_id)->toBe($owner->id)
        ->and($customer->notes)->toBe('Referred by Acme.');
});

it('shows the number of environments belonging to each customer', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    Environment::factory()->for($organization)->count(2)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    Livewire::test(ListCustomers::class)
        ->assertTableColumnStateSet('environments_count', 2, record: $customer);
});
