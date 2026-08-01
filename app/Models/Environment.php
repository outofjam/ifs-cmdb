<?php

namespace App\Models;

use App\Enums\EnvironmentType;
use App\Models\Concerns\BelongsToAuditableOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\FormatsAuditFieldsForPresentation;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @method static Environment|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['customer_id', 'owner_id', 'name', 'type', 'url', 'ifs_release', 'build_number', 'purpose', 'configuration_notes', 'known_issues', 'troubleshooting_notes', 'customer_procedures'])]
class Environment extends Model implements AuditableContract
{
    /** @use HasFactory<EnvironmentFactory> */
    use Auditable, BelongsToAuditableOrganization, BelongsToOrganization, FormatsAuditFieldsForPresentation, HasFactory, HasUuids {
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

    /**
     * A categorized version of this environment's audit trail, for the
     * "Version history" timeline (docs/plan.md §12 "Environment Lifecycle
     * Management"). That section names six event types (creation, refresh,
     * clone, upgrade, deployment, configuration change), but a refresh,
     * clone, or deployment doesn't change any field on Environment -- there's
     * nothing in the audit trail that could signal one happened. Scoped to
     * the three that are actually derivable from data this app tracks:
     * created, upgraded (ifs_release or build_number changed -- a release
     * change wins the label if both changed in the same save), and
     * configuration_changed (any other field).
     *
     * @return Collection<int, array{type: string, label: string, detail: ?string, occurred_at: ?Carbon}>
     */
    public function lifecycleTimeline(): Collection
    {
        return $this->audits()
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(function (Audit $audit): array {
                if ($audit->event === 'created') {
                    $initialRelease = $audit->new_values['ifs_release'] ?? null;

                    return [
                        'type' => 'created',
                        'label' => 'Created',
                        'detail' => filled($initialRelease) ? "Initial release: {$initialRelease}" : null,
                        'occurred_at' => $audit->created_at,
                    ];
                }

                $newValues = $audit->new_values ?? [];
                $oldValues = $audit->old_values ?? [];

                if (array_key_exists('ifs_release', $newValues)) {
                    return [
                        'type' => 'upgraded',
                        'label' => 'Upgraded',
                        'detail' => sprintf('%s → %s', $oldValues['ifs_release'] ?? 'Unknown', $newValues['ifs_release']),
                        'occurred_at' => $audit->created_at,
                    ];
                }

                if (array_key_exists('build_number', $newValues)) {
                    return [
                        'type' => 'upgraded',
                        'label' => 'Upgraded',
                        'detail' => sprintf('Build %s → %s', $oldValues['build_number'] ?? 'Unknown', $newValues['build_number']),
                        'occurred_at' => $audit->created_at,
                    ];
                }

                return [
                    'type' => 'configuration_changed',
                    'label' => 'Configuration changed',
                    'detail' => collect($newValues)->keys()->map(fn (string $field): string => Str::headline($field))->implode(', '),
                    'occurred_at' => $audit->created_at,
                ];
            });
    }
}
