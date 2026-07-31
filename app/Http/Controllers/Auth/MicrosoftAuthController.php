<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ExtractEntraTenantIdFromToken;
use App\Actions\Auth\ProvisionUserFromEntra;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use SocialiteProviders\Azure\Provider as AzureProvider;
use SocialiteProviders\Manager\Config;

/**
 * Two-step Microsoft sign-in: (1) resolve the organization by its slug and
 * redirect to that org's own Entra app, (2) verify the callback's tenant ID
 * actually matches that org before logging anyone in. There is no shared
 * platform-wide Entra app -- an org with no Entra config of its own simply
 * can't use Microsoft sign-in until an admin sets it up.
 */
class MicrosoftAuthController extends Controller
{
    /**
     * Shows the form asking for the org's slug.
     */
    public function start(): View
    {
        return view('auth.microsoft-start');
    }

    /**
     * Resolves the org by slug and either redirects to its Entra app or
     * shows why Microsoft sign-in isn't available for it.
     */
    public function resolve(Request $request): RedirectResponse|View
    {
        $validated = $request->validate(['slug' => 'required|string']);

        $organization = Organization::query()->where('slug', $validated['slug'])->first();

        if ($organization === null) {
            return view('auth.organization-not-found');
        }

        if (! $this->hasOwnEntraConfig($organization)) {
            return view('auth.entra-not-configured');
        }

        session(['microsoft_login_organization_id' => $organization->id]);

        return $this->buildOrgProvider($organization)->redirect();
    }

    /**
     * Verifies the callback's Entra tenant ID against the org resolved in
     * resolve(), then provisions/logs in the user. Rejects on any mismatch.
     */
    public function callback(
        ProvisionUserFromEntra $provisionUserFromEntra,
        ExtractEntraTenantIdFromToken $extractEntraTenantIdFromToken,
    ): RedirectResponse|View {
        $organizationId = session()->pull('microsoft_login_organization_id');

        if ($organizationId === null) {
            return redirect()->route('auth.microsoft.start');
        }

        $organization = Organization::query()->findOrFail($organizationId);
        $entraUser = $this->buildOrgProvider($organization)->user();

        $tenantId = $extractEntraTenantIdFromToken->handle($entraUser->token);

        if ($tenantId === null || $tenantId !== $organization->azure_tenant_id) {
            Log::warning('Rejected Microsoft login: Entra tenant ID did not match the resolved organization.', [
                'organization_id' => $organization->id,
                'expected_tenant_id' => $organization->azure_tenant_id,
                'actual_tenant_id' => $tenantId,
            ]);

            return view('auth.entra-verification-failed');
        }

        $user = $provisionUserFromEntra->handle($entraUser, $organization);

        auth()->login($user);

        return redirect('/admin');
    }

    /**
     * Whether the org has all three Entra values needed to build a provider.
     */
    protected function hasOwnEntraConfig(Organization $organization): bool
    {
        return filled($organization->azure_client_id)
            && filled($organization->azure_client_secret)
            && filled($organization->azure_tenant_id);
    }

    /**
     * Builds a Socialite Azure provider scoped to the org's own Entra app
     * and tenant, so only that tenant's accounts can complete the OAuth flow.
     */
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
