<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
class EntraSettings extends Page
{
    protected string $view = 'filament.pages.entra-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdministrator() ?? false;
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
                    TextInput::make('azure_client_id')
                        ->label('Client ID'),
                    TextInput::make('azure_client_secret')
                        ->label('Client secret')
                        ->password()
                        ->revealable(),
                    TextInput::make('azure_tenant_id')
                        ->label('Tenant ID'),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')->submit('save'),
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
