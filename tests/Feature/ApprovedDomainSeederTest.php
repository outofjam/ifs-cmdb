<?php

use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Database\Seeders\ApprovedDomainSeeder;

it('seeds an approved domain for each configured domain', function () {
    config(['onboarding.seeded_organization_domains' => 'acme.com,acme-hvac.com']);

    $this->seed(ApprovedDomainSeeder::class);

    $domains = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
        ->pluck('domain')
        ->sort()
        ->values();

    expect($domains->all())->toBe(['acme-hvac.com', 'acme.com']);
});

it('attaches every seeded domain to the seeded organization', function () {
    config(['onboarding.seeded_organization_domains' => 'acme.com']);

    $this->seed(ApprovedDomainSeeder::class);

    $organization = Organization::withoutGlobalScope(OrganizationScope::class)->sole();
    $approvedDomain = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->sole();

    expect($approvedDomain->organization_id)->toBe($organization->id);
});

it('is idempotent when run twice', function () {
    config(['onboarding.seeded_organization_domains' => 'acme.com']);

    $this->seed(ApprovedDomainSeeder::class);
    $this->seed(ApprovedDomainSeeder::class);

    expect(ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->count())->toBe(1);
});
