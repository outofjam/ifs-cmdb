<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(ProvisionUserFromEntra $provisionUserFromEntra): RedirectResponse
    {
        $entraUser = Socialite::driver('azure')->user();

        $user = $provisionUserFromEntra->handle($entraUser);

        auth()->login($user);

        return redirect('/admin');
    }
}
