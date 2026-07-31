<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The outcome of the most recent "Verify" check against a Credential's
 * secret_reference. Never derived from or containing the secret value.
 */
enum VerificationStatus: string implements HasColor, HasLabel
{
    case Verified = 'verified';
    case NotFound = 'not_found';
    case Failed = 'failed';

    /**
     * Display name for the Filament badge.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::NotFound => 'Not found',
            self::Failed => 'Check failed',
        };
    }

    /**
     * Badge color for the Filament table/infolist.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::NotFound => 'danger',
            self::Failed => 'warning',
        };
    }
}
