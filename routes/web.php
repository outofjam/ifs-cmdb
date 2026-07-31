<?php

use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/microsoft/start', [MicrosoftAuthController::class, 'start'])
    ->name('auth.microsoft.start');

Route::post('/auth/microsoft/start', [MicrosoftAuthController::class, 'resolve'])
    ->name('auth.microsoft.resolve');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('auth.microsoft.callback');
