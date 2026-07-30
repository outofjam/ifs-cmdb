<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static Organization|static create(array $attributes = [])
 * @method static Builder|static query()
 *
 * @mixin Builder
 */
#[Fillable(['name', 'email_domain'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
