<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Models\Audit as BaseAudit;

/**
 * This app's Audit implementation (see config/audit.php "implementation").
 * UUID-keyed to match every other table, and org-scoped like any other
 * tenant data -- organization_id is populated per-model via the
 * BelongsToAuditableOrganization trait's transformAudit() hook, since
 * these rows are created by the package's observer, not application code.
 */
class Audit extends BaseAudit
{
    use HasUuids;

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
