<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrganizationRole: string implements HasColor, HasLabel
{
    case PlatformAdministrator = 'platform_administrator';
    case DeliveryManager = 'delivery_manager';
    case Consultant = 'consultant';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::PlatformAdministrator => 'Platform Administrator',
            self::DeliveryManager => 'Delivery Manager',
            self::Consultant => 'Consultant',
            self::Viewer => 'Viewer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PlatformAdministrator => 'danger',
            self::DeliveryManager => 'warning',
            self::Consultant => 'info',
            self::Viewer => 'gray',
        };
    }
}
