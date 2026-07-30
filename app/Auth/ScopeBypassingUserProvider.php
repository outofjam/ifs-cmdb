<?php

namespace App\Auth;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Auth\EloquentUserProvider;

class ScopeBypassingUserProvider extends EloquentUserProvider
{
    // Auth lookups (password login, session/remember-me resolution) run before
    // there's an authenticated user, so OrganizationScope's fail-closed behavior
    // with no auth context would otherwise reject every login attempt, always.
    protected function newModelQuery($model = null)
    {
        $query = is_null($model)
            ? $this->createModel()->newQuery()
            : $model->newQuery();

        $query->withoutGlobalScope(OrganizationScope::class);

        with($query, $this->queryCallback);

        return $query;
    }
}
