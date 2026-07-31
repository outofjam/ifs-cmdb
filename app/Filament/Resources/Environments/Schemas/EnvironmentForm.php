<?php

namespace App\Filament\Resources\Environments\Schemas;

use App\Enums\EnvironmentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EnvironmentForm
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
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Acme UAT'),
                        Select::make('type')
                            ->label('Environment type')
                            ->options(EnvironmentType::class)
                            ->required(),
                        TextInput::make('url')
                            ->label('URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->placeholder('https://acme-uat.ifscloud.com'),
                        Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                Section::make('Application metadata')
                    ->description('The IFS Cloud release and build currently deployed here.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(2)
                    ->schema([
                        TextInput::make('ifs_release')
                            ->label('Release')
                            ->helperText('E.g. 24R2 — from the environment\'s Solution Manager.')
                            ->placeholder('24R2'),
                        TextInput::make('build_number')
                            ->label('Build number')
                            ->helperText('From the environment\'s About/System Information page.')
                            ->placeholder('4821'),
                    ]),
                Section::make('Environment knowledge')
                    ->description('Context for anyone picking this environment up cold -- what it\'s for, how it\'s configured, and what tends to go wrong.')
                    ->icon(Heroicon::OutlinedLightBulb)
                    ->schema([
                        Textarea::make('purpose')
                            ->helperText('Why this environment exists and what it\'s used for.')
                            ->columnSpanFull(),
                        Textarea::make('configuration_notes')
                            ->label('Configuration notes')
                            ->helperText('Anything configured differently from a standard environment.')
                            ->columnSpanFull(),
                        Textarea::make('known_issues')
                            ->label('Known issues')
                            ->helperText('Recurring quirks or bugs specific to this environment.')
                            ->columnSpanFull(),
                        Textarea::make('troubleshooting_notes')
                            ->label('Troubleshooting information')
                            ->helperText('Steps that have fixed problems here before.')
                            ->columnSpanFull(),
                        Textarea::make('customer_procedures')
                            ->label('Customer-specific procedures')
                            ->helperText('Anything the customer expects before you touch this environment.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
