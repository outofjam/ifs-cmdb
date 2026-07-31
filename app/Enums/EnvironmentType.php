<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnvironmentType: string implements HasColor, HasLabel
{
    case Production = 'production';
    case Uat = 'uat';
    case Test = 'test';
    case Development = 'development';
    case Training = 'training';
    case Demo = 'demo';
    case Integration = 'integration';

    public function getLabel(): string
    {
        return match ($this) {
            self::Uat => 'UAT',
            default => $this->name,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Production => 'danger',
            self::Uat => 'warning',
            self::Test, self::Integration => 'info',
            self::Development, self::Training, self::Demo => 'gray',
        };
    }
}
