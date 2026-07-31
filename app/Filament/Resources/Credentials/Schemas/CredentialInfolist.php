<?php

namespace App\Filament\Resources\Credentials\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/**
 * The Filament infolist (read-only view) for a Credential.
 */
class CredentialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('environment.name')
                    ->label('Environment'),
                TextEntry::make('name'),
                TextEntry::make('username')
                    ->placeholder('Not set'),
                TextEntry::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unassigned'),
                TextEntry::make('secret_provider')
                    ->label('Secret provider')
                    ->badge(),
                TextEntry::make('secret_reference')
                    ->label('Reference'),
                TextEntry::make('expiration_date')
                    ->date()
                    ->placeholder('No expiration set'),
                TextEntry::make('purpose')
                    ->columnSpanFull()
                    ->placeholder('Not set'),
            ]);
    }
}
