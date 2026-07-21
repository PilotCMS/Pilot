<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('application layouts bootstrap appearance before assets load', function (string $routeName) {
    $response = $this
        ->actingAs(User::factory()->create())
        ->get(route($routeName));

    $response
        ->assertOk()
        ->assertSee('window.Flux = {', false)
        ->assertSee('applyAppearance(appearance)', false)
        ->assertSee("root.setAttribute('data-theme', isDark ? 'dark' : 'light')", false)
        ->assertSee("window.Flux.applyAppearance(window.localStorage.getItem('flux.appearance') || 'system')", false);
})->with([
    'admin.dashboard',
    'profile.edit',
    'appearance.edit',
]);

test('auth layouts bootstrap appearance before assets load', function () {
    $this
        ->get(route('login'))
        ->assertOk()
        ->assertSee('window.Flux = {', false)
        ->assertSee('applyAppearance(appearance)', false)
        ->assertSee("root.classList.toggle('dark', isDark)", false);
});

test('appearance settings expose light dark and system modes', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('Choose how Pilot CMS should render on this device')
        ->assertSee('value="light"', false)
        ->assertSee('value="dark"', false)
        ->assertSee('value="system"', false)
        ->assertDontSee('Dark mode is disabled for this app')
        ->assertDontSee('data-flux-radio-group-segmented disabled', false);
});

test('tailwind and runtime dark mode are enabled', function () {
    expect(File::get(resource_path('css/app.css')))
        ->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->toContain(':root.dark')
        ->toContain('scrollbar-color: var(--border-strong) transparent;')
        ->toContain('background: var(--text-tertiary);');

    expect(File::get(resource_path('css/jaunt/tokens/colors.css')))
        ->toContain('[data-theme="dark"]');

    expect(File::get(resource_path('js/app.js')))
        ->not->toContain('disableDarkMode')
        ->not->toContain("root.classList.remove('dark')")
        ->not->toContain("window.localStorage.getItem('flux.appearance') !== 'light'");
});

test('admin topbar theme control toggles and describes the target appearance', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee("window.Flux.applyAppearance(dark ? 'light' : 'dark')", false)
        ->assertSee("dark ? 'Switch to light mode' : 'Switch to dark mode'", false)
        ->assertSee("dark ? 'Light mode' : 'Dark mode'", false)
        ->assertSee('x-on:pilot-theme-changed.window', false);
});

test('legacy admin listing rows define dark mode dividers and hover states', function () {
    expect(File::get(resource_path('views/livewire/admin/blocks/index.blade.php')))
        ->toContain('dark:border-strong dark:bg-hover dark:text-tertiary')
        ->toContain('dark:border-white/10 dark:hover:bg-white/[0.04]');
});

test('redesigned content listing uses semantic dark mode surfaces', function () {
    expect(File::get(resource_path('views/livewire/admin/content/index.blade.php')))
        ->toContain('cms-shell')
        ->toContain('cms-table-head')
        ->toContain('cms-table-row')
        ->toContain('cms-rail');
});
