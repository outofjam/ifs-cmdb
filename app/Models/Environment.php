<?php

namespace App\Models;

use App\Enums\EnvironmentType;
use App\Models\Concerns\BelongsToAuditableOrganization;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @method static Environment|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['customer_id', 'owner_id', 'name', 'type', 'url', 'ifs_release', 'build_number', 'notes'])]
class Environment extends Model implements AuditableContract
{
    /** @use HasFactory<EnvironmentFactory> */
    use Auditable, BelongsToAuditableOrganization, BelongsToOrganization, HasFactory, HasUuids {
        BelongsToAuditableOrganization::transformAudit insteadof Auditable;
    }

    protected function casts(): array
    {
        return [
            'type' => EnvironmentType::class,
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Credential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }
}
