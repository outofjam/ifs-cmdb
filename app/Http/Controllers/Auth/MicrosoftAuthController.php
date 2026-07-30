<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ProvisionUserFromEntra;
use App\Exceptions\Auth\OrganizationNotApprovedException;
use App\Http\Controllers\Controller;
use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use SocialiteProviders\Azure\Provider as AzureProvider;
use SocialiteProviders\Manager\Config;

class MicrosoftAuthController extends Controller
{
    public function start(): View
    {
        return view('auth.microsoft-start');
    }

    public function resolve(Request $request): RedirectResponse|View
    {
        $validated = $request->validate(['email' => 'required|email']);
        $domain = Str::after($validated['email'], '@');

        $approvedDomain = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
            ->where('domain', $domain)
            ->first();

        if ($approvedDomain === null) {
            return view('auth.organization-not-approved');
        }

        $organization = $approvedDomain->organization;

        if ($this->hasOwnEntraConfig($organization)) {
            session(['microsoft_login_organization_id' => $organization->id]);

            return $this->buildOrgProvider($organization)->redirect();
        }

        session()->forget('microsoft_login_organization_id');

        return Socialite::driver('azure')->redirect();
    }

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(ProvisionUserFromEntra $provisionUserFromEntra): RedirectResponse|View
    {
        $organizationId = session()->pull('microsoft_login_organization_id');

        $entraUser = $organizationId !== null
            ? $this->buildOrgProvider(Organization::query()->findOrFail($organizationId))->user()
            : Socialite::driver('azure')->user();

        try {
            $user = $provisionUserFromEntra->handle($entraUser);
        } catch (OrganizationNotApprovedException) {
            return view('auth.organization-not-approved');
        }

        auth()->login($user);

        return redirect('/admin');
    }

    protected function hasOwnEntraConfig(Organization $organization): bool
    {
        return filled($organization->azure_client_id)
            && filled($organization->azure_client_secret)
            && filled($organization->azure_tenant_id);
    }

    protected function buildOrgProvider(Organization $organization): AbstractProvider
    {
        $redirectUri = route('auth.microsoft.callback');

        $provider = Socialite::buildProvider(AzureProvider::class, [
            'client_id' => $organization->azure_client_id,
            'client_secret' => $organization->azure_client_secret,
            'redirect' => $redirectUri,
        ]);

        $provider->setConfig(new Config(
            $organization->azure_client_id,
            $organization->azure_client_secret,
            $redirectUri,
            ['tenant' => $organization->azure_tenant_id],
        ));

        return $provider;
    }
}
