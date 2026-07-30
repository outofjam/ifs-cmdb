<?php

namespace App\Actions\Auth;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

class ProvisionUserFromEntra
{
    public function handle(SocialiteUser $entraUser): User
    {
        $organization = Organization::query()->firstOrFail();

        // Runs before login, so there's no authenticated user yet — OrganizationScope
        // denies all rows with no auth context, which would otherwise hide any existing
        // user and create a duplicate on every repeat login.
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
