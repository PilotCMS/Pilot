<?php

use App\Models\Space;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('pilot install prompts for the first administrator instead of seeding demo accounts', function () {
    $this->artisan('pilot:install', ['--force' => true])
        ->expectsQuestion('Administrator name', 'Pilot Owner')
        ->expectsQuestion('Email address', 'owner@example.com')
        ->expectsQuestion('Password', 'a-secure-password')
        ->expectsQuestion('Confirm password', 'a-secure-password')
        ->assertSuccessful();

    $admin = User::sole();

    expect($admin->name)->toBe('Pilot Owner')
        ->and($admin->email)->toBe('owner@example.com')
        ->and($admin->hasRole('Admin'))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Space::where('slug', 'main')->exists())->toBeTrue()
        ->and(User::whereIn('email', [
            'admin@pilot.com',
            'editor@pilot.com',
            'author@pilot.com',
            'viewer@pilot.com',
        ])->exists())->toBeFalse();
});

test('database seeding creates reference data without creating users', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::query()->count())->toBe(0)
        ->and(Role::where('name', 'Admin')->exists())->toBeTrue();
});

test('pilot install refuses to invent credentials in a non-interactive environment', function () {
    $this->artisan('pilot:install', ['--force' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Pilot requires an interactive terminal')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});
