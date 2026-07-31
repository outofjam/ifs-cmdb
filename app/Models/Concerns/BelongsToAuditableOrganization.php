<?php

namespace App\Models\Concerns;

/**
 * Stamps organization_id onto every Audit record the package creates for
 * this model, so the audits table can be tenant-scoped like everything
 * else. Package-created Audit rows bypass normal model creation (and so
 * BelongsToOrganization's own creating hook), which is why this needs its
 * own hook into laravel-auditing's transformAudit() extension point.
 */
trait BelongsToAuditableOrganization
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        $data['organization_id'] = $this->organization_id;

        return $data;
    }
}
