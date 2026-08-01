<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Environments\Pages\CreateEnvironment;
use App\Filament\Resources\Environments\Pages\ListEnvironments;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
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
            'purpose' => 'Used for regression testing before each release.',
            'configuration_notes' => 'Mirrors production config except outbound email is disabled.',
            'known_issues' => 'Scheduled jobs occasionally double-fire after a refresh.',
            'troubleshooting_notes' => 'Restart the app server if login hangs.',
            'customer_procedures' => 'Customer requires a Slack heads-up before any refresh.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $environment = Environment::query()->where('name', 'Acme UAT')->sole();

    expect($environment->organization_id)->toBe($organization->id)
        ->and($environment->customer_id)->toBe($customer->id)
        ->and($environment->type)->toBe(EnvironmentType::Uat)
        ->and($environment->owner_id)->toBe($owner->id)
        ->and($environment->ifs_release)->toBe('24R2')
        ->and($environment->build_number)->toBe('4821')
        ->and($environment->purpose)->toBe('Used for regression testing before each release.')
        ->and($environment->configuration_notes)->toBe('Mirrors production config except outbound email is disabled.')
        ->and($environment->known_issues)->toBe('Scheduled jobs occasionally double-fire after a refresh.')
        ->and($environment->troubleshooting_notes)->toBe('Restart the app server if login hangs.')
        ->and($environment->customer_procedures)->toBe('Customer requires a Slack heads-up before any refresh.');
});

it('shows the environment knowledge fields on the view page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'purpose' => 'Used for regression testing before each release.',
        'configuration_notes' => 'Mirrors production config except outbound email is disabled.',
        'known_issues' => 'Scheduled jobs occasionally double-fire after a refresh.',
        'troubleshooting_notes' => 'Restart the app server if login hangs.',
        'customer_procedures' => 'Customer requires a Slack heads-up before any refresh.',
    ]);

    Livewire::test(ViewEnvironment::class, ['record' => $environment->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Used for regression testing before each release.')
        ->assertSee('Mirrors production config except outbound email is disabled.')
        ->assertSee('Scheduled jobs occasionally double-fire after a refresh.')
        ->assertSee('Restart the app server if login hangs.')
        ->assertSee('Customer requires a Slack heads-up before any refresh.');
});

it('hides the environment knowledge section when nothing has been recorded', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
    ]);

    Livewire::test(ViewEnvironment::class, ['record' => $environment->getRouteKey()])
        ->assertSuccessful()
        ->assertDontSee('Environment knowledge');
});

it('shows the environment knowledge section when only one field has been recorded', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'purpose' => 'Used for regression testing before each release.',
    ]);

    Livewire::test(ViewEnvironment::class, ['record' => $environment->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Environment knowledge')
        ->assertSee('Used for regression testing before each release.')
        ->assertSee('None recorded');
});
