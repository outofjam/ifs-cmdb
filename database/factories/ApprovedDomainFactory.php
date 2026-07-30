<?php

namespace Database\Factories;

use App\Models\ApprovedDomain;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovedDomain>
 */
class ApprovedDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'domain' => fake()->unique()->domainName(),
        ];
    }
}
