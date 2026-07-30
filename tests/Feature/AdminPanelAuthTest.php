<?php

use App\Filament\Pages\Auth\MicrosoftLogin;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests away from the admin panel to the login page', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('shows both a Microsoft sign-in link and a password login form', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee(route('auth.microsoft.redirect'), false);
    $response->assertSee('wire:model="data.password"', false);
});

it('lets an authenticated user reach the admin dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});

it('logs in with a password', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'email' => 'jane@acme.test',
        'password' => 'password123',
    ]);

    Livewire::test(MicrosoftLogin::class)
        ->fillForm([
            'email' => 'jane@acme.test',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($user->id);
});
