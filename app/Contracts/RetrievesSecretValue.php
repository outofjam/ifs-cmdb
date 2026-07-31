<?php

namespace App\Contracts;

use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Organization;

/**
 * A port for fetching a Credential's actual secret value, live, from the
 * org's own vault -- the deliberate, audited exception to "never stores
 * secret material" (see CLAUDE.md "Secret Retrieval"). The value returned
 * here must never be persisted, logged, or held beyond a single render.
 */
interface RetrievesSecretValue
{
    /**
     * @throws SecretNotFoundException
     * @throws SecretRetrievalFailedException
     */
    public function retrieve(Organization $organization, string $secretReference): string;
}
