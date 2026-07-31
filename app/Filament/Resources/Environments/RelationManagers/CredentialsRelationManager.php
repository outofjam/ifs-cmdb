<?php

namespace App\Filament\Resources\Environments\RelationManagers;

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\AuditEvent;
use App\Models\Credential;
use App\Services\SecretProviderRetrieverResolver;
use App\Services\SecretProviderVerifierResolver;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Credentials nested under their Environment's view page, rather than a
 * standalone top-level resource -- a credential is always browsed in the
 * context of "this environment's credentials," never as a global list.
 */
class CredentialsRelationManager extends RelationManager
{
    protected static string $relationship = 'credentials';

    /**
     * Hides the whole tab for Production environments -- credentials are
     * non-prod only, so there's nothing to manage here at all. The model's
     * own saving guard is the defense-in-depth backstop.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type !== EnvironmentType::Production;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credential')
                    ->description('What this credential is for.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columns(2)
                    ->schema([
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
                    ->description('A pointer to where the secret actually lives -- the value itself is never stored here.')
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
                            ->helperText('The key/secret name in that provider -- not the value.')
                            ->placeholder('acme-uat-ifs-admin'),
                        DatePicker::make('expiration_date')
                            ->native(false)
                            ->helperText('When this credential should be rotated.'),
                    ]),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
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
            ])
            ->filters([
                SelectFilter::make('secret_provider')
                    ->options(SecretProvider::class),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                static::verifyAction(),
                static::revealAction(),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Checks the credential's secret_reference against its provider's
     * vault. Only shown when that provider has a verifier implemented and
     * the org has configured a vault to check against.
     */
    public static function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verify')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->visible(function (Credential $record): bool {
                if ($record->secret_provider !== SecretProvider::AzureKeyVault) {
                    return false;
                }

                return filled($record->organization->azure_key_vault_url);
            })
            ->action(function (Credential $record): void {
                $verifier = app(SecretProviderVerifierResolver::class)->resolve($record->secret_provider);

                $status = $verifier->verify($record->organization, $record->secret_reference);

                $record->update([
                    'verification_status' => $status,
                    'last_verified_at' => now(),
                ]);

                Notification::make()
                    ->title($status->getLabel())
                    ->color($status->getColor())
                    ->send();
            });
    }

    /**
     * Fetches and displays the credential's real secret value, live, from
     * its provider's vault. Manual trigger only, shown once in a dismissible
     * notification, never persisted or logged, audit-logged per retrieval.
     * See CLAUDE.md "Secret Retrieval".
     */
    public static function revealAction(): Action
    {
        return Action::make('reveal')
            ->label('Reveal')
            ->icon(Heroicon::OutlinedEye)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('This fetches the actual secret value from your vault and displays it once. It is not stored anywhere by this platform.')
            ->visible(function (Credential $record): bool {
                if ($record->secret_provider !== SecretProvider::AzureKeyVault) {
                    return false;
                }

                return filled($record->organization->azure_key_vault_url);
            })
            ->action(function (Credential $record): void {
                $retriever = app(SecretProviderRetrieverResolver::class)->resolve($record->secret_provider);

                try {
                    $value = $retriever->retrieve($record->organization, $record->secret_reference);
                } catch (SecretNotFoundException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                } catch (SecretRetrievalFailedException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                $record->update([
                    'last_retrieved_at' => now(),
                    'last_retrieved_by' => auth()->id(),
                ]);

                AuditEvent::create([
                    'organization_id' => $record->organization_id,
                    'user_id' => auth()->id(),
                    'credential_id' => $record->id,
                    'action' => 'credential_value_retrieved',
                ]);

                Notification::make()
                    ->title('Secret value')
                    ->body($value)
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
