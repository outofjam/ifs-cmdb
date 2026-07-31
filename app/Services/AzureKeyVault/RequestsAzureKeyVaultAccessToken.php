<?php

namespace App\Services\AzureKeyVault;

use App\Models\Organization;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared client-credentials token request against the org's Entra app,
 * scoped to Key Vault. Used by both the reference verifier and the secret
 * retriever so the auth path is identical for both.
 */
trait RequestsAzureKeyVaultAccessToken
{
    /**
     * Requests a client-credentials access token scoped to Key Vault, or
     * null if the request fails for any reason.
     */
    protected function requestAccessToken(Organization $organization): ?string
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->retry(2, 200, throw: false)
                ->post("https://login.microsoftonline.com/{$organization->azure_tenant_id}/oauth2/v2.0/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $organization->azure_client_id,
                    'client_secret' => $organization->azure_client_secret,
                    'scope' => 'https://vault.azure.net/.default',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Azure Key Vault token request could not connect.', [
                'organization_id' => $organization->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Azure Key Vault token request failed.', [
                'organization_id' => $organization->id,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json('access_token');
    }
}
