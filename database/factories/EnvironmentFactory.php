<?php

namespace Database\Factories;

use App\Enums\EnvironmentType;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Environment>
 */
class EnvironmentFactory extends Factory
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
            'customer_id' => Customer::factory(),
            'name' => fake()->word().' '.fake()->randomElement(['UAT', 'Test', 'Dev']),
            'type' => fake()->randomElement(EnvironmentType::cases()),
            'url' => fake()->url(),
            'ifs_release' => fake()->randomElement(['24R1', '24R2', '25R1']),
            'build_number' => (string) fake()->numberBetween(1000, 9999),
            'purpose' => null,
            'configuration_notes' => null,
            'known_issues' => null,
            'troubleshooting_notes' => null,
            'customer_procedures' => null,
        ];
    }
}
