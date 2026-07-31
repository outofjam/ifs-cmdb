<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The external vault a Credential's secret_reference points into. The
 * platform never stores the secret value itself -- see docs/plan.md §9.1.
 */
enum SecretProvider: string implements HasColor, HasLabel
{
    case AzureKeyVault = 'azure_key_vault';
    case OnePassword = 'one_password';
    case Bitwarden = 'bitwarden';
    case HashicorpVault = 'hashicorp_vault';
    case AwsSecretsManager = 'aws_secrets_manager';

    /**
     * Display name for the Filament select/badge.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::AzureKeyVault => 'Azure Key Vault',
            self::OnePassword => '1Password',
            self::Bitwarden => 'Bitwarden',
            self::HashicorpVault => 'HashiCorp Vault',
            self::AwsSecretsManager => 'AWS Secrets Manager',
        };
    }

    /**
     * Badge color for the Filament table/infolist.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::AzureKeyVault => 'info',
            self::OnePassword => 'primary',
            self::Bitwarden => 'warning',
            self::HashicorpVault => 'gray',
            self::AwsSecretsManager => 'success',
        };
    }
}
