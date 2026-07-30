<?php

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Enums\OrganizationRole;
use App\Exceptions\Auth\OrganizationNotApprovedException;
use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

it('creates a new user attached to the approved domain\'s organization on first login', function () {
    $organization = Organization::factory()->create();
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-consulting.test']);

    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-123',
        'email' => 'new.consultant@acme-consulting.test',
        'name' => 'New Consultant',
    ]);

    $user = (new ProvisionUserFromEntra)->handle($entraUser);

    expect($user->email)->toBe('new.consultant@acme-consulting.test')
        ->and($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(OrganizationRole::Viewer);
});

it('reuses the existing user on a repeat login instead of duplicating', function () {
    $organization = Organization::factory()->create();
    ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->create(['organization_id' => $organization->id, 'domain' => 'acme-consulting.test']);

    $existing = User::withoutGlobalScope(OrganizationScope::class)
        ->create([
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

    $user = (new ProvisionUserFromEntra)->handle($entraUser);

    expect($user->id)->toBe($existing->id)
        ->and(User::withoutGlobalScope(OrganizationScope::class)->count())->toBe(1);
});

it('rejects login from a domain with no approved organization', function () {
    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-789',
        'email' => 'someone@unapproved-company.test',
        'name' => 'Someone',
    ]);

    expect(fn () => (new ProvisionUserFromEntra)->handle($entraUser))
        ->toThrow(OrganizationNotApprovedException::class);

    expect(Organization::withoutGlobalScope(OrganizationScope::class)->count())->toBe(0)
        ->and(User::withoutGlobalScope(OrganizationScope::class)->count())->toBe(0);
});

it('rejects login from a personal email domain the same way as any unapproved domain', function () {
    $entraUser = (new SocialiteUser)->map([
        'id' => 'entra-oid-999',
        'email' => 'someone@gmail.com',
        'name' => 'Someone',
    ]);

    expect(fn () => (new ProvisionUserFromEntra)->handle($entraUser))
        ->toThrow(OrganizationNotApprovedException::class);
});
