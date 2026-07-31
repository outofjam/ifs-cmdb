<?php

namespace App\Services\AzureKeyVault;

use App\Contracts\RetrievesSecretValue;
use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Organization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a secret's current value from an org's Azure Key Vault. Unlike
 * the reference verifier, this calls the plain "get secret" endpoint,
 * which does return the value -- that's the entire point of a retriever.
 * Never logs the response body; only status codes on failure.
 */
class AzureKeyVaultSecretRetriever implements RetrievesSecretValue
{
    use RequestsAzureKeyVaultAccessToken;

    public function retrieve(Organization $organization, string $secretReference): string
    {
        $token = $this->requestAccessToken($organization);

        if ($token === null) {
            throw new SecretRetrievalFailedException;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->retry(2, 200, throw: false)
                ->get(rtrim($organization->azure_key_vault_url, '/')."/secrets/{$secretReference}", [
                    'api-version' => '7.4',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Azure Key Vault retrieval request could not connect.', [
                'organization_id' => $organization->id,
                'message' => $exception->getMessage(),
            ]);

            throw new SecretRetrievalFailedException;
        }

        if ($response->status() === 404) {
            throw new SecretNotFoundException;
        }

        if (! $response->successful()) {
            Log::warning('Azure Key Vault retrieval request failed.', [
                'organization_id' => $organization->id,
                'status' => $response->status(),
            ]);

            throw new SecretRetrievalFailedException;
        }

        $value = $response->json('value');

        if (blank($value)) {
            throw new SecretNotFoundException;
        }

        return $value;
    }
}
