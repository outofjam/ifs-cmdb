<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General information')
                    ->description('Who this customer is and who owns the relationship.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Acme Corp'),
                        Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                Section::make('Notes')
                    ->description('Anything worth knowing about this customer relationship.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
