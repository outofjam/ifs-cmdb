<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Deliberately narrow: only the two abilities tapp/filament-auditing's
 * AuditsRelationManager checks (`audit` for the tab, `restoreAudit` for the
 * rollback action). No standard CRUD methods (viewAny/view/create/update/
 * delete) are defined here -- Filament only defers to a policy method that
 * actually exists on the class, so leaving those undefined preserves the
 * app's existing unrestricted CRUD behavior instead of silently denying it.
 */
class CustomerPolicy
{
    /**
     * Any user can view audit history for a customer in their own
     * organization -- org-scoping is the only access control this app has
     * today, matching every other Customer action.
     */
    public function audit(User $user, Customer $customer): bool
    {
        return $user->organization_id === $customer->organization_id;
    }

    /**
     * Rolling back a record to a previous audited state is a bigger,
     * riskier capability than viewing history and wasn't requested --
     * denied for everyone until it is.
     */
    public function restoreAudit(User $user, Customer $customer): bool
    {
        return false;
    }
}
