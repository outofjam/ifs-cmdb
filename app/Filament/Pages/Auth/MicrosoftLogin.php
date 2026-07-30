<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class MicrosoftLogin extends BaseLogin
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            $this->getFormContentComponent(),
            $this->getMultiFactorChallengeFormContentComponent(),
            Actions::make([
                Action::make('microsoft')
                    ->label('Sign in with Microsoft')
                    ->url(route('auth.microsoft.start'))
                    ->button()
                    ->outlined()
                    ->extraAttributes(['class' => 'w-full']),
            ]),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
        ]);
    }
}
