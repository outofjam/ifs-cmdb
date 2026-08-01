<?php

namespace App\Filament\Resources\Environments\Schemas;

use App\Models\Environment;
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
                Section::make('Environment knowledge')
                    ->description('Context for anyone picking this environment up cold -- what it\'s for, how it\'s configured, and what tends to go wrong.')
                    ->icon(Heroicon::OutlinedLightBulb)
                    ->columnSpanFull()
                    // Hidden entirely when every field is empty -- a freshly
                    // created environment otherwise shows five "Not set" rows,
                    // which is just noise. Shown as soon as one field has
                    // content, with placeholders on whatever's still blank.
                    ->visible(fn (Environment $record): bool => filled($record->purpose)
                        || filled($record->configuration_notes)
                        || filled($record->known_issues)
                        || filled($record->troubleshooting_notes)
                        || filled($record->customer_procedures))
                    ->schema([
                        TextEntry::make('purpose')
                            ->placeholder('Not set')
                            ->columnSpanFull(),
                        TextEntry::make('configuration_notes')
                            ->label('Configuration notes')
                            ->placeholder('Not set')
                            ->columnSpanFull(),
                        TextEntry::make('known_issues')
                            ->label('Known issues')
                            ->placeholder('None recorded')
                            ->columnSpanFull(),
                        TextEntry::make('troubleshooting_notes')
                            ->label('Troubleshooting information')
                            ->placeholder('Not set')
                            ->columnSpanFull(),
                        TextEntry::make('customer_procedures')
                            ->label('Customer-specific procedures')
                            ->placeholder('Not set')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
