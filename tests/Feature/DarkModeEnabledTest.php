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
    expect(File::get(base_path('vendor/pilotcms/core/resources/css/app.css')))
        ->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->toContain(':root.dark')
        ->toContain('scrollbar-color: var(--border-strong) transparent;')
        ->toContain('background: var(--text-tertiary);');

    expect(File::get(base_path('vendor/pilotcms/core/resources/css/jaunt/tokens/colors.css')))
        ->toContain('[data-theme="dark"]')
        ->toContain('--gray-800:  #2b303b;')
        ->toContain('--gray-950:  #1d2027;')
        ->toContain('--text-secondary:  var(--gray-300);');

    expect(File::get(base_path('vendor/pilotcms/core/resources/css/jaunt/tokens/materials.css')))
        ->toContain('--material-chrome-bg:    rgba(33, 37, 45, 0.72);');

    expect(File::get(base_path('vendor/pilotcms/core/resources/js/app.js')))
        ->not->toContain('disableDarkMode')
        ->not->toContain("root.classList.remove('dark')")
        ->not->toContain("window.localStorage.getItem('flux.appearance') !== 'light'");
});

test('admin topbar theme control toggles and describes the target appearance', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('$flux.appearance = dark ? \'light\' : \'dark\'', false)
        ->assertDontSee("window.Flux.applyAppearance(dark ? 'light' : 'dark')", false)
        ->assertSee("dark ? 'Switch to light mode' : 'Switch to dark mode'", false)
        ->assertSee("dark ? 'Light mode' : 'Dark mode'", false)
        ->assertSee('x-on:pilot-theme-changed.window', false);
});

test('legacy admin listing rows define dark mode dividers and hover states', function () {
    expect(File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/blocks/index.blade.php')))
        ->toContain('dark:border-strong dark:bg-hover dark:text-tertiary')
        ->toContain('dark:border-white/10 dark:hover:bg-white/[0.04]');
});

test('redesigned content listing uses semantic dark mode surfaces', function () {
    expect(File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/content/index.blade.php')))
        ->toContain('cms-shell')
        ->toContain('cms-table-head')
        ->toContain('cms-table-row')
        ->toContain('cms-rail');
});

test('dark cards share resting and hover elevation', function () {
    $styles = File::get(base_path('vendor/pilotcms/core/resources/css/app.css'));
    $cards = File::get(base_path('vendor/pilotcms/core/resources/views/components/jaunt/data/card.blade.php'));
    $assets = File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/assets/index.blade.php'));

    expect($styles)
        ->toContain('[data-theme="dark"] .cms-panel {')
        ->toContain('[data-theme="dark"] .cms-panel[class*="transition-shadow"]')
        ->toContain('[data-theme="dark"] .cms-fab:hover');

    expect($cards)
        ->toContain('shadow-sm dark:shadow-sm')
        ->toContain('hover:shadow-md dark:hover:shadow-md');

    expect($assets)
        ->toContain('shadow-xs dark:shadow-sm')
        ->toContain('hover:shadow-md dark:hover:shadow-md');
});

test('dashboard continue editing cards use explicit resting and hover states', function () {
    $styles = File::get(base_path('vendor/pilotcms/core/resources/css/app.css'));
    $dashboard = File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/dashboard.blade.php'));

    expect($dashboard)
        ->toContain('class="dashboard-continue-editing"')
        ->toContain('cms-panel dashboard-continue-editing-card block transition-shadow');

    expect($styles)
        ->toContain('.cms-panel.dashboard-continue-editing-card,')
        ->toContain('.cms-panel.dashboard-continue-editing-card:hover,')
        ->toContain("box-shadow: var(--shadow-sm);\n        transform: translateY(0);")
        ->toContain("box-shadow: var(--shadow-md);\n        transform: translateY(-1px);");
});
