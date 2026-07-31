<?php

namespace App\Actions\Auth;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Attaches (or creates) the local User record for a Microsoft-authenticated
 * login. The organization has already been resolved and its Entra tenant ID
 * verified by MicrosoftAuthController::callback() before this runs -- this
 * class only handles the local user record.
 */
class ProvisionUserFromEntra
{
    /**
     * Finds the existing user by email under the given organization, or
     * creates a new Viewer-role user attached to it.
     */
    public function handle(SocialiteUser $entraUser, Organization $organization): User
    {
        // Runs before login, so there's no authenticated user yet -- OrganizationScope
        // denies all rows with no auth context, which would otherwise hide any
        // existing user and create a duplicate on every repeat login.
        return User::withoutGlobalScope(OrganizationScope::class)->firstOrCreate(
            ['email' => $entraUser->getEmail()],
            [
                'name' => $entraUser->getName(),
                'organization_id' => $organization->id,
                'role' => OrganizationRole::Viewer,
                'password' => str()->random(40),
            ],
        );
    }
}
