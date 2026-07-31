<?php

namespace App\Policies;

use App\Models\Environment;
use App\Models\User;

/**
 * Deliberately narrow: only the two abilities tapp/filament-auditing's
 * AuditsRelationManager checks (`audit` for the tab, `restoreAudit` for the
 * rollback action). No standard CRUD methods (viewAny/view/create/update/
 * delete) are defined here -- Filament only defers to a policy method that
 * actually exists on the class, so leaving those undefined preserves the
 * app's existing unrestricted CRUD behavior instead of silently denying it.
 */
class EnvironmentPolicy
{
    /**
     * Any user can view audit history for an environment in their own
     * organization -- org-scoping is the only access control this app has
     * today, matching every other Environment action.
     */
    public function audit(User $user, Environment $environment): bool
    {
        return $user->organization_id === $environment->organization_id;
    }

    /**
     * Rolling back a record to a previous audited state is a bigger,
     * riskier capability than viewing history and wasn't requested --
     * denied for everyone until it is.
     */
    public function restoreAudit(User $user, Environment $environment): bool
    {
        return false;
    }
}
