<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;

class MicrosoftLogin extends BaseLogin
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Actions::make([
                Action::make('microsoft')
                    ->label('Sign in with Microsoft')
                    ->url(route('auth.microsoft.redirect'))
                    ->button()
                    ->extraAttributes(['class' => 'w-full']),
            ]),
        ]);
    }
}
