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

it('generates a slug from the name on create', function () {
    $organization = Organization::factory()->create(['name' => 'Acme HVAC']);

    expect($organization->slug)->toBe('acme-hvac');
});

it('does not override an explicitly given slug', function () {
    $organization = Organization::factory()->create([
        'name' => 'Acme HVAC',
        'slug' => 'custom-slug',
    ]);

    expect($organization->slug)->toBe('custom-slug');
});

it('appends a numeric suffix when the generated slug collides', function () {
    Organization::factory()->create(['name' => 'Acme HVAC']);
    $second = Organization::factory()->create(['name' => 'Acme HVAC']);
    $third = Organization::factory()->create(['name' => 'Acme HVAC']);

    expect($second->slug)->toBe('acme-hvac-2')
        ->and($third->slug)->toBe('acme-hvac-3');
});

it('enforces slug uniqueness at the database level', function () {
    Organization::factory()->create(['slug' => 'acme-hvac']);

    expect(fn () => Organization::factory()->create(['slug' => 'acme-hvac']))
        ->toThrow(QueryException::class);
});
