<?php

namespace App\Filament\Resources\Environments\Pages;

use App\Filament\Resources\Environments\EnvironmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEnvironment extends EditRecord
{
    protected static string $resource = EnvironmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
