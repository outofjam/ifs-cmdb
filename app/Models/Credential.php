<?php

namespace App\Models;

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Enums\VerificationStatus;
use App\Exceptions\CredentialTargetsProductionEnvironmentException;
use App\Models\Concerns\BelongsToAuditableOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\FormatsAuditFieldsForPresentation;
use App\Models\Scopes\OrganizationScope;
use Database\Factories\CredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * A reference to a secret held in an external vault -- never the secret
 * value itself. Non-production environments only, enforced below.
 *
 * @method static Credential|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['environment_id', 'owner_id', 'name', 'purpose', 'username', 'secret_provider', 'secret_reference', 'expiration_date', 'last_verified_at', 'verification_status', 'last_retrieved_at', 'last_retrieved_by'])]
class Credential extends Model implements AuditableContract
{
    /** @use HasFactory<CredentialFactory> */
    use Auditable, BelongsToAuditableOrganization, BelongsToOrganization, FormatsAuditFieldsForPresentation, HasFactory, HasUuids {
        BelongsToAuditableOrganization::transformAudit insteadof Auditable;
    }

    /**
     * Rejects saving a Credential against a Production environment,
     * regardless of how it was reached (form, API, tinker).
     */
    protected static function booted(): void
    {
        static::saving(function (Credential $credential): void {
            $environment = Environment::withoutGlobalScope(OrganizationScope::class)
                ->find($credential->environment_id);

            if ($environment?->type === EnvironmentType::Production) {
                throw new CredentialTargetsProductionEnvironmentException;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret_provider' => SecretProvider::class,
            'expiration_date' => 'date',
            'last_verified_at' => 'datetime',
            'verification_status' => VerificationStatus::class,
            'last_retrieved_at' => 'datetime',
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
     * @return BelongsTo<Environment, $this>
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastRetrievedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_retrieved_by');
    }
}
