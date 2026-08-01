<?php

use App\Enums\EnvironmentType;
use App\Livewire\CredentialAuditHistoryTable;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('lists this credential\'s audit history', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'Original name',
    ]);
    $credential->update(['name' => 'Renamed credential']);

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credential])
        ->assertSuccessful()
        ->assertSee($user->name)
        ->assertSee('Renamed credential')
        ->assertSee('Original name');
});

it('does not show another credential\'s audit history', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credentialA = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'Credential A',
    ]);
    $credentialB = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'Credential B',
    ]);
    $credentialB->update(['name' => 'Renamed B']);

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credentialA])
        ->assertDontSee('Renamed B');
});

it('shows a related record\'s name instead of a raw UUID', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $owner = User::factory()->for($organization)->create(['name' => 'Ada Lovelace']);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
    ]);
    $credential->update(['owner_id' => $owner->id]);

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credential])
        ->assertSee('Ada Lovelace')
        ->assertDontSee($owner->id);
});

it('is searchable by event', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
    ]);
    $credential->update(['name' => 'Renamed']);

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credential])
        ->searchTable('created')
        ->assertSee('Created')
        ->assertDontSee('Renamed');
});

it('is sortable by when the change happened', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
    ]);

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credential])
        ->sortTable('created_at')
        ->assertSuccessful()
        ->sortTable('created_at', 'desc')
        ->assertSuccessful();
});

it('paginates a large audit history instead of rendering it all at once', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'name' => 'Original name',
    ]);

    // 1 "created" audit already exists from the factory. 25 more renames
    // is well past a single page.
    foreach (range(1, 25) as $i) {
        $credential->update(['name' => "Renamed {$i}"]);
    }

    Livewire::test(CredentialAuditHistoryTable::class, ['credential' => $credential])
        ->assertSuccessful()
        // Real Filament pagination, not a hand-rolled cap -- proven by the
        // presence of its own "Per page" control, and every row actually
        // reachable rather than silently dropped past a fixed limit.
        ->assertSee('Per page')
        ->assertSee('Renamed 25')
        ->set('tableRecordsPerPage', 5)
        ->assertCountTableRecords(26);
});
