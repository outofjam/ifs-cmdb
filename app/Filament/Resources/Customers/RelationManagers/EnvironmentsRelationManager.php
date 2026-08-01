<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\EnvironmentType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * A customer's environments, browsable and fully manageable from the
 * customer's own page -- mirrors EnvironmentForm/EnvironmentInfolist's
 * sections, minus the Customer field itself (implicit from context here,
 * and set automatically via the `environments` relationship on create --
 * never offered as a pickable field, which would let a record wander to a
 * different customer than the page it was created from).
 */
class EnvironmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'environments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General information')
                    ->description('What this environment is and who owns it.')
                    ->icon(Heroicon::OutlinedServer)
                    ->columns(2)
                    ->schema([
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
                            ->preload(),
                    ]),
                Section::make('Application metadata')
                    ->description('The IFS Cloud release and build currently deployed here.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columns(2)
                    ->schema([
                        TextInput::make('ifs_release')
                            ->label('Release')
                            ->helperText('E.g. 24R2 -- from the environment\'s Solution Manager.')
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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->placeholder('Unassigned'),
                TextEntry::make('ifs_release')
                    ->label('Release')
                    ->placeholder('Unknown'),
                TextEntry::make('build_number')
                    ->label('Build number')
                    ->placeholder('Unknown'),
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('Unassigned'),
                TextColumn::make('ifs_release')
                    ->label('Release')
                    ->placeholder('Unknown')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(EnvironmentType::class),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
