<?php

namespace App\Filament\Resources\Environments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EnvironmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General information')
                    ->description('What this environment is and who owns it.')
                    ->icon(Heroicon::OutlinedServer)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer.name')
                            ->label('Customer')
                            ->icon(Heroicon::OutlinedBuildingOffice2),
                        TextEntry::make('name')
                            ->icon(Heroicon::OutlinedTag),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('url')
                            ->label('URL')
                            ->icon(Heroicon::OutlinedLink)
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->copyable()
                            ->placeholder('Not set'),
                        TextEntry::make('owner.name')
                            ->label('Owner')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('Unassigned')
                            ->columnSpanFull(),
                    ]),
                Section::make('Application metadata')
                    ->description('The IFS Cloud release and build currently deployed here.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ifs_release')
                            ->label('Release')
                            ->placeholder('Unknown'),
                        TextEntry::make('build_number')
                            ->label('Build number')
                            ->placeholder('Unknown'),
                    ]),
                Section::make('Notes')
                    ->description('Configuration details, known issues, and procedures specific to this environment.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->placeholder('No notes yet.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
