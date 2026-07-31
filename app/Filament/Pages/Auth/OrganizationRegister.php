<?php

namespace App\Filament\Pages\Auth;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class OrganizationRegister extends BaseRegister
{
    /**
     * Builds the signup form: organization name plus the base Register
     * page's name/email/password fields.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getOrganizationNameFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    /**
     * The org-name field used to name the brand-new organization created on
     * submit.
     */
    protected function getOrganizationNameFormComponent(): Component
    {
        return TextInput::make('organization_name')
            ->label('Organization name')
            ->required()
            ->maxLength(255);
    }

    /**
     * Every signup creates its own brand-new organization -- never joins an
     * existing one. Domain string matching can't prove ownership, so it must
     * never grant access to data that already belongs to someone else.
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $organization = Organization::query()->create(['name' => $data['organization_name']]);

        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $organization->id,
            'role' => OrganizationRole::PlatformAdministrator,
        ]);
    }
}
