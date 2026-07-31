<?php

namespace Database\Factories;

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Models\Credential;
use App\Models\Environment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
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
            // Never Production by default -- the model guard would reject it,
            // and tests that don't care about the guard shouldn't be flaky.
            'environment_id' => Environment::factory()->state(['type' => EnvironmentType::Uat]),
            'name' => fake()->randomElement(['IFS Administrator', 'Database Read-Only', 'API Integration User']),
            'purpose' => fake()->sentence(),
            'username' => fake()->userName(),
            'secret_provider' => fake()->randomElement(SecretProvider::cases()),
            'secret_reference' => fake()->slug(3),
            'expiration_date' => null,
        ];
    }
}
