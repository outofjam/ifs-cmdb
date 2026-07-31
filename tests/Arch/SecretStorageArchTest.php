<?php

use Illuminate\Support\Facades\File;

it('never adds a raw-secret-shaped column outside the allowed auth columns', function () {
    // users.password/remember_token are hashed auth credentials, not vault
    // secret material -- everything else must only store a provider +
    // reference pointer, never a value. See docs/plan.md §9.1.
    $allowedFiles = [
        '0001_01_01_000000_create_users_table.php',
    ];

    $forbiddenColumnPattern = '/\$table->(?:string|text)\([\'"](?:password|secret|secret_value|api_key|token|access_token|refresh_token|client_secret)[\'"]/';

    $violations = collect(File::allFiles(database_path('migrations')))
        ->reject(fn ($file) => in_array($file->getFilename(), $allowedFiles, true))
        ->filter(fn ($file) => preg_match($forbiddenColumnPattern, $file->getContents()) === 1)
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

it('never exposes a method that returns a decrypted or raw secret value', function () {
    $forbiddenMethodPattern = '/function\s+(get\w*Secret\w*Value|decrypt\w*(Password|Secret)|reveal\w*Secret)\s*\(/i';

    $violations = collect(File::allFiles(app_path()))
        ->filter(fn ($file) => preg_match($forbiddenMethodPattern, $file->getContents()) === 1)
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($violations)->toBe([]);
});
