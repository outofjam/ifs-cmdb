<?php

namespace App\Filament\Resources\Credentials\Schemas;

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Filament form for creating/editing a Credential.
 */
class CredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credential')
                    ->description('What this credential is for and where it applies.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columns(2)
                    ->schema([
                        Select::make('environment_id')
                            ->label('Environment')
                            ->relationship(
                                'environment',
                                'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('type', '!=', EnvironmentType::Production->value),
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Production environments never appear here — credentials are non-prod only.'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('IFS Administrator'),
                        TextInput::make('username')
                            ->placeholder('ifsadmin'),
                        Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('purpose')
                            ->columnSpanFull(),
                    ]),
                Section::make('Secret reference')
                    ->description('A pointer to where the secret actually lives — the value itself is never stored here.')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->columns(2)
                    ->schema([
                        Select::make('secret_provider')
                            ->label('Secret provider')
                            ->options(SecretProvider::class)
                            ->required(),
                        TextInput::make('secret_reference')
                            ->label('Reference')
                            ->required()
                            ->prefixIcon(Heroicon::OutlinedFingerPrint)
                            ->helperText('The key/secret name in that provider — not the value.')
                            ->placeholder('acme-uat-ifs-admin'),
                        DatePicker::make('expiration_date')
                            ->native(false)
                            ->helperText('When this credential should be rotated.'),
                    ]),
            ]);
    }
}
