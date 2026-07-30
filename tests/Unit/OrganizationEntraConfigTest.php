<?php

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

it('stores and retrieves an organization\'s own Entra app config', function () {
    $organization = Organization::factory()->create([
        'azure_client_id' => 'client-123',
        'azure_client_secret' => 'super-secret-value',
        'azure_tenant_id' => 'tenant-456',
    ]);

    $fresh = $organization->fresh();

    expect($fresh->azure_client_id)->toBe('client-123')
        ->and($fresh->azure_client_secret)->toBe('super-secret-value')
        ->and($fresh->azure_tenant_id)->toBe('tenant-456');
});

it('never stores the client secret in plaintext', function () {
    $organization = Organization::factory()->create([
        'azure_client_secret' => 'super-secret-value',
    ]);

    $raw = DB::table('organizations')->where('id', $organization->id)->value('azure_client_secret');

    expect($raw)->not->toBe('super-secret-value');
});

it('defaults to no Entra config', function () {
    $organization = Organization::factory()->create();

    expect($organization->fresh()->azure_client_id)->toBeNull()
        ->and($organization->fresh()->azure_client_secret)->toBeNull()
        ->and($organization->fresh()->azure_tenant_id)->toBeNull();
});
