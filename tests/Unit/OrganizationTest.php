<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;

it('can create an organization', function () {
    $organization = Organization::factory()->create([
        'name' => 'ABC Consulting',
    ]);

    expect($organization->name)->toBe('ABC Consulting')
        ->and($organization->exists)->toBeTrue();
});

it('a user belongs to an organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();

    expect($user->organization_id)->toBe($organization->id)
        ->and($user->organization->is($organization))->toBeTrue();
});

it('rejects a second organization with the same email domain', function () {
    Organization::factory()->create(['email_domain' => 'acme.com']);

    expect(fn () => Organization::factory()->create(['email_domain' => 'acme.com']))
        ->toThrow(QueryException::class);
});
