<?php

use App\Actions\Auth\ExtractEntraTenantIdFromToken;

it('extracts the tid claim from a token payload', function () {
    $token = fakeJwt(['tid' => 'tenant-abc-123', 'oid' => 'user-456']);

    expect((new ExtractEntraTenantIdFromToken)->handle($token))->toBe('tenant-abc-123');
});

it('returns null when the token has no tid claim', function () {
    $token = fakeJwt(['oid' => 'user-456']);

    expect((new ExtractEntraTenantIdFromToken)->handle($token))->toBeNull();
});

it('returns null for a malformed token', function () {
    expect((new ExtractEntraTenantIdFromToken)->handle('not-a-jwt'))->toBeNull();
});
