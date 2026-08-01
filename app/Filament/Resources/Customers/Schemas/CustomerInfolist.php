<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General information')
                    ->description('Who this customer is and who owns the relationship.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->icon(Heroicon::OutlinedIdentification),
                        TextEntry::make('owner.name')
                            ->label('Owner')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('Unassigned'),
                    ]),
                Section::make('Notes')
                    ->description('Anything worth knowing about this customer relationship.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->visible(fn (Customer $record): bool => filled($record->notes))
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
