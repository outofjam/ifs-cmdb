<?php

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

it('stores and retrieves the organization\'s Bitwarden access token', function () {
    $organization = Organization::factory()->create([
        'bitwarden_access_token' => '0.access-token-value',
    ]);

    expect($organization->fresh()->bitwarden_access_token)->toBe('0.access-token-value');
});

it('never stores the Bitwarden access token in plaintext', function () {
    $organization = Organization::factory()->create([
        'bitwarden_access_token' => '0.access-token-value',
    ]);

    $raw = DB::table('organizations')->where('id', $organization->id)->value('bitwarden_access_token');

    expect($raw)->not->toBe('0.access-token-value');
});

it('defaults to no Bitwarden config', function () {
    $organization = Organization::factory()->create();

    expect($organization->fresh()->bitwarden_access_token)->toBeNull();
});
