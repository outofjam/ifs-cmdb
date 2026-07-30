<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case PlatformAdministrator = 'platform_administrator';
    case DeliveryManager = 'delivery_manager';
    case Consultant = 'consultant';
    case Viewer = 'viewer';
}
