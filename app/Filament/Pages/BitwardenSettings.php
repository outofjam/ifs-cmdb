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
 * Lets an org admin configure the machine-account access token this
 * platform uses to reveal Bitwarden Secrets Manager credential values.
 *
 * @property-read Schema $form
 */
class BitwardenSettings extends Page
{
    protected string $view = 'filament.pages.bitwarden-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Bitwarden';

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
        return 'Bitwarden Secrets Manager';
    }

    public function getSubheading(): string
    {
        return "Connect a Bitwarden Secrets Manager machine account so your team can reveal credential values stored there, directly from a credential's page.";
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->only([
            'bitwarden_access_token',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Machine account')
                        ->description('In the Bitwarden web app: Secrets Manager → Machine accounts → your account → Access tokens → Create access token.')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->schema([
                            TextInput::make('bitwarden_access_token')
                                ->label('Access token')
                                ->prefixIcon(Heroicon::OutlinedKey)
                                ->password()
                                ->revealable()
                                ->helperText('Shown only once when created — copy it immediately. Grants read access to every secret the machine account can see, so treat it like a password. Stored encrypted.'),
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
