<?php

namespace App\Filament\Resources\Environments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EnvironmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('url')
                    ->label('URL')
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab(),
                TextEntry::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unassigned'),
                TextEntry::make('ifs_release')
                    ->label('Release')
                    ->placeholder('Unknown'),
                TextEntry::make('build_number')
                    ->label('Build number')
                    ->placeholder('Unknown'),
                TextEntry::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
