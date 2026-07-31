<?php

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit', 'Arch');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Builds an unsigned JWT string for tests that exercise Entra access-token
 * claim extraction. Signature isn't checked by the app (see
 * ExtractEntraTenantIdFromToken's docblock for why), so a fake one is fine.
 *
 * @param  array<string, mixed>  $payload
 */
function fakeJwt(array $payload): string
{
    $base64url = fn (string $data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

    return $base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
        .'.'.$base64url(json_encode($payload))
        .'.fake-signature';
}

/**
 * An organization with a full Entra app + Key Vault config, for Azure Key
 * Vault verifier/retriever tests.
 */
function organizationWithVaultConfig(): Organization
{
    return Organization::factory()->create([
        'azure_client_id' => 'client-123',
        'azure_client_secret' => 'super-secret',
        'azure_tenant_id' => 'tenant-456',
        'azure_key_vault_url' => 'https://acme-vault.vault.azure.net',
    ]);
}

/**
 * A UAT-environment Credential (Azure Key Vault provider) belonging to an
 * org with a full vault config, with the returning user authenticated.
 * Used by the Verify/Reveal Filament action tests.
 *
 * @param  string|null  $vaultUrl  pass null to test the "no vault configured" case
 */
function credentialWithVaultConfig(?string $vaultUrl = 'https://acme-vault.vault.azure.net'): Credential
{
    $organization = Organization::factory()->create([
        'azure_client_id' => 'client-123',
        'azure_client_secret' => 'super-secret',
        'azure_tenant_id' => 'tenant-456',
        'azure_key_vault_url' => $vaultUrl,
    ]);
    $user = User::factory()->for($organization)->create();
    test()->actingAs($user);

    $customer = Customer::factory()->for($organization)->create();
    $environment = Environment::factory()->for($organization)->create([
        'customer_id' => $customer->id,
        'type' => EnvironmentType::Uat,
    ]);

    return Credential::factory()->for($organization)->create([
        'environment_id' => $environment->id,
        'secret_provider' => SecretProvider::AzureKeyVault,
        'secret_reference' => 'acme-uat-ifs-admin',
    ]);
}
