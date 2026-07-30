<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class EntraSettings extends Page
{
    protected string $view = 'filament.pages.entra-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Microsoft Entra ID';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdministrator() ?? false;
    }

    public function getTitle(): string
    {
        return 'Microsoft Entra ID';
    }

    public function getSubheading(): string
    {
        return "Connect your organization's own Entra app registration so your team signs in with Microsoft through it, instead of the shared platform app.";
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->only([
            'azure_client_id',
            'azure_client_secret',
            'azure_tenant_id',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('App registration')
                        ->description('Found in the Azure Portal under Entra ID → App registrations → your app.')
                        ->icon(Heroicon::OutlinedBuildingOffice2)
                        ->columns(2)
                        ->schema([
                            TextInput::make('azure_client_id')
                                ->label('Client ID')
                                ->prefixIcon(Heroicon::OutlinedIdentification)
                                ->helperText('The "Application (client) ID" on the app\'s Overview page.')
                                ->placeholder('00000000-0000-0000-0000-000000000000'),
                            TextInput::make('azure_tenant_id')
                                ->label('Tenant ID')
                                ->prefixIcon(Heroicon::OutlinedBuildingLibrary)
                                ->helperText('The "Directory (tenant) ID" on the app\'s Overview page.')
                                ->placeholder('00000000-0000-0000-0000-000000000000'),
                            TextInput::make('azure_client_secret')
                                ->label('Client secret')
                                ->prefixIcon(Heroicon::OutlinedKey)
                                ->password()
                                ->revealable()
                                ->columnSpanFull()
                                ->helperText('From Certificates & secrets → Client secrets. Copy the secret\'s value, not its ID — it\'s only shown once. Stored encrypted.'),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save changes')
                                ->icon(Heroicon::OutlinedCheck)
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->getRecord()->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Saved')
            ->send();
    }

    public function getRecord(): Organization
    {
        return auth()->user()->organization;
    }
}
