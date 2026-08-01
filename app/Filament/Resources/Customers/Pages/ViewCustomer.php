<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getSubheading(): string
    {
        $count = $this->record->environments()->count();

        return "{$count} ".Str::plural('environment', $count);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
