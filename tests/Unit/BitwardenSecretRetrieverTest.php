<?php

use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Organization;
use App\Services\Bitwarden\BitwardenSecretRetriever;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

function organizationWithBitwardenConfig(): Organization
{
    return Organization::factory()->create([
        'bitwarden_access_token' => '0.access-token-value',
    ]);
}

it('returns the secret value when found', function () {
    Process::fake([
        '*bws*' => Process::result(
            output: json_encode(['id' => 'be8e0ad8-d545-4017-a55a-b02f014d4158', 'value' => 'super-secret-password']),
        ),
    ]);

    $value = (new BitwardenSecretRetriever)->retrieve(organizationWithBitwardenConfig(), 'be8e0ad8-d545-4017-a55a-b02f014d4158');

    expect($value)->toBe('super-secret-password');
});

it('throws SecretNotFoundException when bws reports a 404', function () {
    Process::fake([
        '*bws*' => Process::result(
            output: '',
            errorOutput: 'Error: 0: Received error message from server: [404 Not Found] Resource not found.',
            exitCode: 1,
        ),
    ]);

    expect(fn () => (new BitwardenSecretRetriever)->retrieve(organizationWithBitwardenConfig(), 'no-such-secret'))
        ->toThrow(SecretNotFoundException::class);
});

it('throws SecretRetrievalFailedException on any other failure', function () {
    Process::fake([
        '*bws*' => Process::result(
            output: '',
            errorOutput: 'Error: 0: Received error message from server: [401 Unauthorized] Invalid access token.',
            exitCode: 1,
        ),
    ]);

    expect(fn () => (new BitwardenSecretRetriever)->retrieve(organizationWithBitwardenConfig(), 'some-id'))
        ->toThrow(SecretRetrievalFailedException::class);
});

it('throws SecretRetrievalFailedException when the process times out', function () {
    Process::fake(function () {
        throw new ProcessTimedOutException(
            new Symfony\Component\Process\Exception\ProcessTimedOutException(
                new Symfony\Component\Process\Process(['bws']),
                Symfony\Component\Process\Exception\ProcessTimedOutException::TYPE_GENERAL,
            ),
        );
    });

    expect(fn () => (new BitwardenSecretRetriever)->retrieve(organizationWithBitwardenConfig(), 'some-id'))
        ->toThrow(SecretRetrievalFailedException::class);
});

it('never passes the access token as part of the command, only via the environment', function () {
    $organization = organizationWithBitwardenConfig();

    Process::fake([
        '*bws*' => Process::result(output: json_encode(['value' => 'super-secret-password'])),
    ]);

    (new BitwardenSecretRetriever)->retrieve($organization, 'some-id');

    Process::assertRan(function (PendingProcess $process): bool {
        $commandString = implode(' ', (array) $process->command);

        return ! str_contains($commandString, $process->environment['BWS_ACCESS_TOKEN'] ?? '__unset__')
            && ($process->environment['BWS_ACCESS_TOKEN'] ?? null) === '0.access-token-value';
    });
});

it('is configured only when the organization has an access token', function () {
    $retriever = new BitwardenSecretRetriever;

    expect($retriever->isConfigured(organizationWithBitwardenConfig()))->toBeTrue()
        ->and($retriever->isConfigured(Organization::factory()->create()))->toBeFalse();
});
