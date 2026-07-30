<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ApprovedDomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static ApprovedDomain|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['organization_id', 'domain'])]
class ApprovedDomain extends Model
{
    /** @use HasFactory<ApprovedDomainFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
