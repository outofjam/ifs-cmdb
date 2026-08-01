<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuditableOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\FormatsAuditFieldsForPresentation;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @method static Customer|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['name', 'owner_id', 'notes'])]
class Customer extends Model implements AuditableContract
{
    /** @use HasFactory<CustomerFactory> */
    use Auditable, BelongsToAuditableOrganization, BelongsToOrganization, FormatsAuditFieldsForPresentation, HasFactory, HasUuids {
        BelongsToAuditableOrganization::transformAudit insteadof Auditable;
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
