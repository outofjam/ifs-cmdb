<?php

arch('tenant models never call withoutGlobalScope outside seeders/console')
    ->expect('App\Models')
    ->not->toUse(['withoutGlobalScope', 'withoutGlobalScopes'])
    ->ignoring('App\Console');
