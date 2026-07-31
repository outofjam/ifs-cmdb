<?php

namespace App\Filament\Resources\Environments\Pages;

use App\Filament\Resources\Environments\EnvironmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEnvironment extends ViewRecord
{
    protected static string $resource = EnvironmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
