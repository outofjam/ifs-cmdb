<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\OrganizationRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                static::changeRoleAction(),
            ]);
    }

    /**
     * Hidden for the acting admin's own row -- role changes require a
     * direct DB edit today (see CLAUDE.md), so this is the only way to
     * grant/revoke admin access; letting someone demote themselves through
     * it risks locking every admin out of the org with no self-service
     * way back in.
     */
    public static function changeRoleAction(): Action
    {
        return Action::make('changeRole')
            ->label('Change role')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->visible(fn (User $record): bool => $record->id !== auth()->id())
            ->fillForm(fn (User $record): array => ['role' => $record->role->value])
            ->schema([
                Select::make('role')
                    ->label('Role')
                    ->options(OrganizationRole::class)
                    ->required(),
            ])
            ->action(function (User $record, array $data): void {
                $record->update(['role' => $data['role']]);

                Notification::make()
                    ->title('Role updated')
                    ->success()
                    ->send();
            });
    }
}
