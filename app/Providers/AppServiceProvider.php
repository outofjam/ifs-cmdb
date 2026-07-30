<?php

namespace App\Providers;

use App\Auth\ScopeBypassingUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, 'SocialiteProviders\\Azure\\AzureExtendSocialite');

        Auth::provider('scope-bypassing-eloquent', function ($app, array $config) {
            return new ScopeBypassingUserProvider($app['hash'], $config['model']);
        });
    }
}
