<?php

use Illuminate\Support\Facades\File;

it('never calls withoutGlobalScope or withoutGlobalScopes in App\Models', function () {
    $modelsPath = __DIR__.'/../../app/Models';
    $violations = collect(File::allFiles($modelsPath))
        ->filter(fn ($file) => str_contains($file->getContents(), 'withoutGlobalScope'))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($violations)->toBe([]);
});
