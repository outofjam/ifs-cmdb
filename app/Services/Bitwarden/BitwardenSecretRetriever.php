<?php

namespace App\Services\Bitwarden;

use App\Contracts\RetrievesSecretValue;
use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Fetches a secret's current value from an org's Bitwarden Secrets Manager
 * by shelling out to the `bws` CLI -- Secrets Manager is end-to-end
 * encrypted, so there's no plain REST endpoint to call the way Key Vault
 * has. The access token is passed via the child process's environment,
 * never as a command-line argument -- CLI args are visible to any user on
 * the same host via `ps`/`/proc/{pid}/cmdline`, env vars scoped to a
 * child process are not. Never logs stdout on success (it contains the
 * value); only exit code/stderr on failure.
 */
class BitwardenSecretRetriever implements RetrievesSecretValue
{
    public function retrieve(Organization $organization, string $secretReference): string
    {
        if (! $this->isConfigured($organization)) {
            throw new SecretRetrievalFailedException;
        }

        try {
            $result = Process::env(['BWS_ACCESS_TOKEN' => $organization->bitwarden_access_token])
                ->timeout(10)
                ->run(['bws', 'secret', 'get', $secretReference, '--output', 'json']);
        } catch (Throwable $exception) {
            Log::warning('Bitwarden secret retrieval could not run.', [
                'organization_id' => $organization->id,
                'message' => $exception->getMessage(),
            ]);

            throw new SecretRetrievalFailedException;
        }

        if (! $result->successful()) {
            if ($this->indicatesNotFound($result->errorOutput())) {
                throw new SecretNotFoundException;
            }

            Log::warning('Bitwarden secret retrieval failed.', [
                'organization_id' => $organization->id,
                'exit_code' => $result->exitCode(),
            ]);

            throw new SecretRetrievalFailedException;
        }

        $decoded = json_decode($result->output(), true);
        $value = is_array($decoded) ? ($decoded['value'] ?? null) : null;

        if (blank($value)) {
            throw new SecretNotFoundException;
        }

        return $value;
    }

    public function isConfigured(Organization $organization): bool
    {
        return filled($organization->bitwarden_access_token);
    }

    /**
     * bws has no structured exit-code-per-failure-type contract, so this
     * matches the "404"/"Not Found" text it's documented to print for a
     * missing secret. Anything else is treated as a generic failure.
     */
    protected function indicatesNotFound(string $errorOutput): bool
    {
        return str_contains($errorOutput, '404') || str_contains(strtolower($errorOutput), 'not found');
    }
}
