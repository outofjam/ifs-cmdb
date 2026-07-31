<?php

namespace App\Filament\Resources\Credentials\Tables;

use App\Enums\SecretProvider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * The Filament table listing Credentials.
 */
class CredentialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('environment.name')
                    ->label('Environment')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('secret_provider')
                    ->label('Provider')
                    ->badge()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unassigned'),
                TextColumn::make('expiration_date')
                    ->date()
                    ->placeholder('None')
                    ->sortable()
                    ->color(fn (?Carbon $state): ?string => $state?->isPast() ? 'danger' : null),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('environment')
                    ->relationship('environment', 'name'),
                SelectFilter::make('secret_provider')
                    ->options(SecretProvider::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
