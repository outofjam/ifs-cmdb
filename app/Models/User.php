<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizationRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @method static User|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['name', 'email', 'password', 'organization_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use BelongsToOrganization, HasFactory, HasUuids, Notifiable;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => OrganizationRole::class,
        ];
    }

    public function isPlatformAdministrator(): bool
    {
        return $this->role === OrganizationRole::PlatformAdministrator;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Single-tenant MVP: every user belongs to the one seeded organization,
        // so authentication alone is sufficient. Revisit if a second org ships.
        return true;
    }
}
