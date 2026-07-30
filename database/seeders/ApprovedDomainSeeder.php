<?php

namespace Database\Seeders;

use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApprovedDomainSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OrganizationSeeder::class);

        $organization = Organization::withoutGlobalScope(OrganizationScope::class)->firstOrFail();

        $domains = array_filter(Str::of(config('onboarding.seeded_organization_domains'))
            ->explode(',')
            ->map(fn (string $domain) => trim($domain))
            ->all());

        foreach ($domains as $domain) {
            ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
                ['domain' => $domain],
                ['organization_id' => $organization->id],
            );
        }
    }
}
