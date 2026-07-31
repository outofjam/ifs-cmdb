<?php

use App\Enums\EnvironmentType;
use App\Models\AuditEvent;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;

it('can create an audit event for a credential retrieval', function () {
    $organization = Organization::factory()->create();
    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);
    $credential = Credential::factory()->for($organization)->create(['environment_id' => $environment->id]);
    $user = User::factory()->for($organization)->create();

    $this->actingAs($user);

    $event = AuditEvent::factory()->for($organization)->create([
        'user_id' => $user->id,
        'credential_id' => $credential->id,
        'action' => 'credential_value_retrieved',
    ]);

    expect($event->action)->toBe('credential_value_retrieved')
        ->and($event->user->is($user))->toBeTrue()
        ->and($event->credential->is($credential))->toBeTrue()
        ->and($event->organization->is($organization))->toBeTrue();
});
