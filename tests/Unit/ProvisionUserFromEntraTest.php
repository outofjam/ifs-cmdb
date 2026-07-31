<?php

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

it('creates a new user attached to the given organization on first login', function () {
    $organization = Organization::factory()->create();

    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'new.consultant@acme-consulting.test',
        'name' => 'New Consultant',
    ]);

    $user = (new ProvisionUserFromEntra)->handle($entraUser, $organization);

    expect($user->email)->toBe('new.consultant@acme-consulting.test')
        ->and($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::Viewer);
});

it('reuses the existing user on a repeat login instead of duplicating', function () {
    $organization = Organization::factory()->create();

    $existing = User::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Sarah Smith',
        'email' => 'sarah@acme-consulting.test',
        'password' => str()->random(40),
    ]);

    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-456',
        'email' => 'sarah@acme-consulting.test',
        'name' => 'Sarah Smith',
    ]);

    $user = (new ProvisionUserFromEntra)->handle($entraUser, $organization);

    expect($user->id)->toBe($existing->id)
        ->and(User::withoutGlobalScope(OrganizationScope::class)->count())->toBe(1);
});
