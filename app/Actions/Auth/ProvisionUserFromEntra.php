<?php

namespace App\Actions\Auth;

use App\Enums\OrganizationRole;
use App\Exceptions\Auth\OrganizationNotApprovedException;
use App\Models\ApprovedDomain;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;

class ProvisionUserFromEntra
{
    public function handle(SocialiteUser $entraUser): User
    {
        $domain = Str::after($entraUser->getEmail(), '@');

        // Runs before login, so there's no authenticated user yet — OrganizationScope
        // denies all rows with no auth context, which would otherwise hide any approved
        // domain / existing user and (for the user lookup) create a duplicate on every
        // repeat login.
        $approvedDomain = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
            ->where('domain', $domain)
            ->first();

        if ($approvedDomain === null) {
            throw new OrganizationNotApprovedException($domain);
        }

        return User::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
            ['email' => $entraUser->getEmail()],
            [
                'name' => $entraUser->getName(),
                'organization_id' => $approvedDomain->organization_id,
                'role' => OrganizationRole::Viewer,
                'password' => str()->random(40),
            ],
        );
    }
}
