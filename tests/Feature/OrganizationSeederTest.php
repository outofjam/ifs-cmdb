<?php

use App\Models\Organization;
use Database\Seeders\OrganizationSeeder;

it('seeds exactly one organization', function () {
    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->count())->toBe(1);
});

it('is idempotent when run twice', function () {
    $this->seed(OrganizationSeeder::class);
    $this->seed(OrganizationSeeder::class);

    expect(Organization::query()->count())->toBe(1);
});
