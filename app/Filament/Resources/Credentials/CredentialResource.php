<?php

namespace App\Filament\Resources\Credentials;

use App\Enums\SecretProvider;
use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Filament\Resources\Credentials\Pages\CreateCredential;
use App\Filament\Resources\Credentials\Pages\EditCredential;
use App\Filament\Resources\Credentials\Pages\ListCredentials;
use App\Filament\Resources\Credentials\Pages\ViewCredential;
use App\Filament\Resources\Credentials\Schemas\CredentialForm;
use App\Filament\Resources\Credentials\Schemas\CredentialInfolist;
use App\Filament\Resources\Credentials\Tables\CredentialsTable;
use App\Models\AuditEvent;
use App\Models\Credential;
use App\Services\SecretProviderRetrieverResolver;
use App\Services\SecretProviderVerifierResolver;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The Filament resource for managing Credentials.
 */
class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function form(Schema $schema): Schema
    {
        return CredentialForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CredentialInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CredentialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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

    public static function getPages(): array
    {
        return [
            'index' => ListCredentials::route('/'),
            'create' => CreateCredential::route('/create'),
            'view' => ViewCredential::route('/{record}'),
            'edit' => EditCredential::route('/{record}/edit'),
        ];
    }
}
