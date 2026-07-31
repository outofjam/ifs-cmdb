<?php

use App\Enums\EnvironmentType;
use App\Filament\Resources\Environments\Pages\EditEnvironment;
use App\Filament\Resources\Environments\RelationManagers\CredentialsRelationManager;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

it('only offers secret providers that actually have a working integration', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $this->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    $response = Livewire::test(CredentialsRelationManager::class, [
        'ownerRecord' => $environment,
        'pageClass' => EditEnvironment::class,
    ])->mountAction(TestAction::make(CreateAction::class)->table());

    $response->assertSee('Azure Key Vault')
        ->assertSee('Bitwarden')
        ->assertDontSee('1Password')
        ->assertDontSee('HashiCorp Vault')
        ->assertDontSee('AWS Secrets Manager');
});
