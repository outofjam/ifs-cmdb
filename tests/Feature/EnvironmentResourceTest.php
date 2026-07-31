<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Environments\Pages\CreateEnvironment;
use App\Filament\Resources\Environments\Pages\ListEnvironments;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lists environments belonging to the authenticated user\'s organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'name' => 'Acme UAT',
    ]);

    Livewire::test(ListEnvironments::class)
        ->assertCanSeeTableRecords([$environment])
        ->assertSee('Acme UAT');
});

it('does not list another organization\'s environments', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $user = User::factory()->for($orgA)->create();
    $this->actingAs($user);

    $customerB = Customer::factory()->for($orgB)->create();
    $otherEnvironment = Environment::factory()->for($orgB)->create([
        'customer_id' => $customerB->id,
    ]);

    Livewire::test(ListEnvironments::class)
        ->assertCanNotSeeTableRecords([$otherEnvironment]);
});

it('creates an environment with a customer, type, and release through the form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $customer = Customer::factory()->for($organization)->create();
    $owner = User::factory()->for($organization)->create();
    $this->actingAs($user);

    Livewire::test(CreateEnvironment::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'name' => 'Acme UAT',
            'type' => EnvironmentType::Uat->value,
            'url' => 'https://acme-uat.ifscloud.com',
            'owner_id' => $owner->id,
            'ifs_release' => '24R2',
            'build_number' => '4821',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $environment = Environment::query()->where('name', 'Acme UAT')->sole();

    expect($environment->organization_id)->toBe($organization->id)
        ->and($environment->customer_id)->toBe($customer->id)
        ->and($environment->type)->toBe(EnvironmentType::Uat)
        ->and($environment->owner_id)->toBe($owner->id)
        ->and($environment->ifs_release)->toBe('24R2')
        ->and($environment->build_number)->toBe('4821');
});
