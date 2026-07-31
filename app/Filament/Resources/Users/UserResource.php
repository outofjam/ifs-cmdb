<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Lets an org admin see who's in their organization and change a
 * teammate's role -- the only way to do either today is a direct DB edit
 * (see CLAUDE.md's "no org-admin user management UI" backlog note).
 * Deliberately list-only: no create (users are provisioned via signup or
 * Entra, never by an admin through this UI) and no delete (removing a
 * user wasn't asked for and is a bigger decision than this ticket covers).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    /**
     * Admin-only. Everything else in this app is open to any authenticated
     * org member (see CLAUDE.md), but role management is the first
     * genuinely sensitive, admin-scoped capability -- gated here directly
     * on the Resource rather than via a Policy, since introducing a
     * UserPolicy would route every other User-related Gate check in the
     * app through it too, not just this one screen.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isPlatformAdministrator() ?? false;
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
