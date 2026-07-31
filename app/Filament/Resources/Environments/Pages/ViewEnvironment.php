<?php

namespace App\Filament\Resources\Environments\Pages;

use App\Filament\Resources\Environments\EnvironmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEnvironment extends ViewRecord
{
    protected static string $resource = EnvironmentResource::class;

    public function getSubheading(): string
    {
        return "{$this->record->customer->name} · {$this->record->type->getLabel()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
