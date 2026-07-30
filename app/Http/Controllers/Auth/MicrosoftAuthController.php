<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Exceptions\Auth\OrganizationNotApprovedException;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(ProvisionUserFromEntra $provisionUserFromEntra): RedirectResponse|View
    {
        $entraUser = Socialite::driver('azure')->user();

        try {
            $user = $provisionUserFromEntra->handle($entraUser);
        } catch (OrganizationNotApprovedException) {
            return view('auth.organization-not-approved');
        }

        auth()->login($user);

        return redirect('/admin');
    }
}
