<?php

namespace App\Filament\Resources\Environments;

use App\Filament\Resources\Environments\Pages\CreateEnvironment;
use App\Filament\Resources\Environments\Pages\EditEnvironment;
use App\Filament\Resources\Environments\Pages\ListEnvironments;
use App\Filament\Resources\Environments\Pages\ViewEnvironment;
use App\Filament\Resources\Environments\Schemas\EnvironmentForm;
use App\Filament\Resources\Environments\Schemas\EnvironmentInfolist;
use App\Filament\Resources\Environments\Tables\EnvironmentsTable;
use App\Models\Environment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EnvironmentResource extends Resource
{
    protected static ?string $model = Environment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function form(Schema $schema): Schema
    {
        return EnvironmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EnvironmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnvironmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnvironments::route('/'),
            'create' => CreateEnvironment::route('/create'),
            'view' => ViewEnvironment::route('/{record}'),
            'edit' => EditEnvironment::route('/{record}/edit'),
        ];
    }
}
