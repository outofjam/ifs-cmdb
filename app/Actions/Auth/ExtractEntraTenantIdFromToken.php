<?php

namespace App\Actions\Auth;

/**
 * Reads the `tid` (tenant ID) claim out of a Microsoft Entra access token,
 * without verifying its signature.
 *
 * Signature verification is unnecessary here: the token was obtained
 * directly from Microsoft's token endpoint via our own server-to-server
 * request (the OAuth2 authorization code exchange), over TLS -- it was never
 * accepted as a bare, client-supplied credential. The `tid` claim is what
 * `MicrosoftAuthController::callback()` checks against the organization's
 * stored `azure_tenant_id` to confirm the login actually came from that
 * organization's own Entra tenant.
 */
class ExtractEntraTenantIdFromToken
{
    /**
     * Returns the `tid` claim from the given JWT access token, or null if
     * the token is malformed or carries no `tid` claim.
     */
    public function handle(string $accessToken): ?string
    {
        $segments = explode('.', $accessToken);

        if (count($segments) !== 3) {
            return null;
        }

        $payload = $this->decodeSegment($segments[1]);

        return $payload['tid'] ?? null;
    }

    /**
     * Base64url-decodes a JWT segment into its JSON payload as an array.
     *
     * @return array<string, mixed>
     */
    protected function decodeSegment(string $segment): array
    {
        $base64 = strtr($segment, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);

        $decoded = json_decode(base64_decode($base64) ?: '', true);

        return is_array($decoded) ? $decoded : [];
    }
}
