<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Extensions\ExtensionRegistry;
use Pilot\Core\Livewire\Admin\CommandPalette;
use Pilot\Core\Livewire\Admin\Dashboard;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    Route::middleware(['web', 'auth'])
        ->get('/admin/test-extension', Dashboard::class)
        ->name('admin.extensions.testing');
    Route::getRoutes()->refreshNameLookups();
});

it('appends extension navigation and page titles without replacing core entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    app(ExtensionRegistry::class)
        ->navigationItem(
            section: 'admin',
            route: 'admin.extensions.testing',
            label: 'Test extension',
            icon: 'settings',
            active: 'admin.extensions.testing',
        )
        ->pageTitle('admin.extensions.testing', 'Test extension');

    $this->actingAs($admin)
        ->get(route('admin.extensions.testing'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Settings')
        ->assertSee('Test extension')
        ->assertSee('<title>Test extension · Pilot CMS</title>', false);
});

it('adds permission-aware extension commands to quick links and search', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    app(ExtensionRegistry::class)->commandPaletteItem(
        group: 'Extensions',
        route: 'admin.extensions.testing',
        title: 'Test extension',
        description: 'Configure the test integration',
        icon: 'settings',
        permission: 'manage settings',
    );

    Livewire::actingAs($admin)
        ->test(CommandPalette::class)
        ->assertSee('Extensions')
        ->assertSee('Test extension')
        ->set('search', 'integration')
        ->assertSee('Test extension')
        ->set('search', 'unrelated')
        ->assertDontSee('Test extension');
});

it('nests extension navigation registered for settings beneath the core settings link', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    app(ExtensionRegistry::class)->navigationItem(
        section: 'settings',
        route: 'admin.extensions.testing',
        label: 'Test settings extension',
        icon: 'plug',
        permission: 'manage settings',
    );

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Settings', 'Test settings extension'])
        ->assertSee('data-pilot-settings-navigation', false);
});

it('hides extension navigation and commands when permission is missing', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    app(ExtensionRegistry::class)
        ->navigationItem(
            section: 'workspace',
            route: 'admin.extensions.testing',
            label: 'Restricted extension',
            icon: 'settings',
            permission: 'manage settings',
        )
        ->commandPaletteItem(
            group: 'Extensions',
            route: 'admin.extensions.testing',
            title: 'Restricted extension',
            description: 'Only visible with permission',
            icon: 'settings',
            permission: 'manage settings',
        );

    $this->actingAs($author)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Restricted extension');

    Livewire::actingAs($author)
        ->test(CommandPalette::class)
        ->assertDontSee('Restricted extension');
});

it('rejects unknown navigation sections', function () {
    app(ExtensionRegistry::class)->navigationItem(
        section: 'unknown',
        route: 'admin.extensions.testing',
        label: 'Test extension',
        icon: 'settings',
    );
})->throws(InvalidArgumentException::class, 'Unknown Pilot navigation section [unknown].');
