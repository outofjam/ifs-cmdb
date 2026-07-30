<?php

namespace App\Filament\Pages\Auth;

use App\Enums\OrganizationRole;
use App\Models\ApprovedDomain;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SensitiveParameter;

class OrganizationRegister extends BaseRegister
{
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

    protected function getOrganizationNameFormComponent(): Component
    {
        return TextInput::make('organization_name')
            ->label('Organization name')
            ->required()
            ->maxLength(255);
    }

    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        $domain = Str::after($data['email'], '@');

        $approvedDomain = ApprovedDomain::withoutGlobalScope(OrganizationScope::class)
            ->where('domain', $domain)
            ->first();

        if ($approvedDomain !== null) {
            return User::withoutGlobalScope(OrganizationScope::class)->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'organization_id' => $approvedDomain->organization_id,
                'role' => OrganizationRole::Viewer,
            ]);
        }

        $organization = Organization::query()->create(['name' => $data['organization_name']]);

        ApprovedDomain::withoutGlobalScope(OrganizationScope::class)->create([
            'organization_id' => $organization->id,
            'domain' => $domain,
        ]);

        return User::withoutGlobalScope(OrganizationScope::class)->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $organization->id,
            'role' => OrganizationRole::PlatformAdministrator,
        ]);
    }
}
