<?php

namespace App\Filament\Resources\Environments\RelationManagers;

use App\Enums\EnvironmentType;
use App\Enums\SecretProvider;
use App\Exceptions\SecretNotFoundException;
use App\Exceptions\SecretRetrievalFailedException;
use App\Models\Audit;
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
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

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
                            ->options($this->selectableSecretProviderOptions())
                            ->helperText('Only providers with a working integration are listed.')
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
                TextColumn::make('last_verified_at')
                    ->label('Last verified')
                    ->since()
                    ->placeholder('Never')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('secret_provider')
                    ->options($this->selectableSecretProviderOptions()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                static::verifyAction(),
                static::revealAction(),
                static::viewAuditHistoryAction(),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * secret_provider => label options for only the providers with a real
     * integration -- never offer a provider the platform can't actually
     * reach as though it were a working choice.
     *
     * @return array<string, string>
     */
    protected function selectableSecretProviderOptions(): array
    {
        return collect(app(SecretProviderVerifierResolver::class)->implementedProviders())
            ->mapWithKeys(fn (SecretProvider $provider): array => [$provider->value => $provider->getLabel()])
            ->all();
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
                $verifier = app(SecretProviderVerifierResolver::class)->resolve($record->secret_provider);

                return $verifier?->isConfigured($record->organization) ?? false;
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
                $retriever = app(SecretProviderRetrieverResolver::class)->resolve($record->secret_provider);

                return $retriever?->isConfigured($record->organization) ?? false;
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
                    ->body(sprintf(
                        '<pre class="max-w-full overflow-x-auto whitespace-pre-wrap break-all rounded-lg bg-gray-100 px-3 py-2 font-mono text-sm text-gray-950 dark:bg-white/5 dark:text-white">%s</pre>',
                        e($value),
                    ))
                    ->success()
                    ->persistent()
                    ->actions([
                        Action::make('copy')
                            ->label('Copy to clipboard')
                            ->icon(Heroicon::OutlinedClipboardDocument)
                            // Purely client-side: the Notifications Livewire
                            // component doesn't implement HasActions, so the
                            // default wire:click="mountAction(...)" handler
                            // would 500. alpineClickHandler() replaces that
                            // default entirely instead of adding alongside it.
                            // Swaps the whole button's content to "Copied!"
                            // for a couple seconds, then restores the original
                            // icon+label markup -- saving/restoring innerHTML
                            // outright instead of hunting for a specific child
                            // node, since Filament gives no CSS hook for the
                            // label alone and a child-node search proved too
                            // fragile against the button's actual markup.
                            // The swap itself fades via a plain CSS opacity
                            // transition rather than an instant cut.
                            ->alpineClickHandler(
                                'window.navigator.clipboard.writeText('.Js::from($value).');'
                                .'const original = $el.innerHTML;'
                                .'$el.style.transition = '.Js::from('opacity 150ms ease').';'
                                .'$el.style.opacity = 0;'
                                .'setTimeout(() => { $el.textContent = '.Js::from('Copied!').'; $el.style.opacity = 1; }, 150);'
                                .'setTimeout(() => { $el.style.opacity = 0; }, 2000);'
                                .'setTimeout(() => { $el.innerHTML = original; $el.style.opacity = 1; }, 2150);'
                            ),
                    ])
                    ->send();
            });
    }

    /**
     * Credential has no resource page of its own to hang the package's
     * AuditsRelationManager off of (it's nested under Environment, see the
     * class docblock), so its audit trail is shown here instead: a row
     * action that opens a modal listing that credential's Audit records.
     */
    public static function viewAuditHistoryAction(): Action
    {
        return Action::make('viewAuditHistory')
            ->label('Audit history')
            ->icon(Heroicon::OutlinedClock)
            ->color('gray')
            ->modalHeading(fn (Credential $record): string => "Audit history: {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn (Credential $record): View => view(
                'filament.credentials.audit-history',
                ['audits' => static::presentableAudits($record)],
            ));
    }

    /**
     * Resolves each audit's changed fields into display-ready
     * [label, old, new] triples -- foreign keys like owner_id are
     * resolved to the related record's name via the same
     * `filament-auditing.mapping` config the package's own Audits tabs
     * use (see App\Models\Concerns\FormatsAuditFieldsForPresentation).
     * Attached as an in-memory property on each Audit, never persisted.
     *
     * @return Collection<int, Audit>
     */
    protected static function presentableAudits(Credential $record): Collection
    {
        return $record->audits()->with('user')->latest()->get()->each(
            function (Audit $audit) use ($record): void {
                $audit->presentableFieldChanges = collect($audit->new_values ?? [])
                    ->map(function (mixed $newValue, string $field) use ($record, $audit): array {
                        [$label, $displayNew] = $record->resolveAuditFieldForDisplay($field, $newValue);
                        $oldValue = $audit->old_values[$field] ?? null;

                        return [
                            'label' => Str::headline($label),
                            'old' => filled($oldValue) ? $record->resolveAuditFieldForDisplay($field, $oldValue)[1] : null,
                            'new' => $displayNew,
                        ];
                    })
                    ->values();
            }
        );
    }
}
