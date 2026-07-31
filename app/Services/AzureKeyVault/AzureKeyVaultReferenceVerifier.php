<?php

namespace App\Services\AzureKeyVault;

use App\Contracts\VerifiesSecretReference;
use App\Enums\VerificationStatus;
use App\Models\Organization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checks whether a secret name exists in an org's Azure Key Vault via the
 * "list versions" endpoint, which returns version metadata only -- never
 * the secret value. Auth is a client-credentials grant against the org's
 * own Entra app, scoped to the Key Vault resource.
 */
class AzureKeyVaultReferenceVerifier implements VerifiesSecretReference
{
    use RequestsAzureKeyVaultAccessToken;

    public function verify(Organization $organization, string $secretReference): VerificationStatus
    {
        $token = $this->requestAccessToken($organization);

        if ($token === null) {
            return VerificationStatus::Failed;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->retry(2, 200, throw: false)
                ->get(rtrim($organization->azure_key_vault_url, '/')."/secrets/{$secretReference}/versions", [
                    'api-version' => '7.4',
                    'maxresults' => 1,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Azure Key Vault verification request could not connect.', [
                'organization_id' => $organization->id,
                'message' => $exception->getMessage(),
            ]);

            return VerificationStatus::Failed;
        }

        if ($response->status() === 404) {
            return VerificationStatus::NotFound;
        }

        if (! $response->successful()) {
            Log::warning('Azure Key Vault verification request failed.', [
                'organization_id' => $organization->id,
                'status' => $response->status(),
            ]);

            return VerificationStatus::Failed;
        }

        $versions = $response->json('value', []);

        return filled($versions) ? VerificationStatus::Verified : VerificationStatus::NotFound;
    }
}
