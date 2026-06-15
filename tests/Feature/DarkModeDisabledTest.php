<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('application layouts do not bootstrap dark mode', function (string $routeName) {
    $response = $this
        ->actingAs(User::factory()->create())
        ->get(route($routeName));

    $response
        ->assertOk()
        ->assertDontSee('class="dark"', false)
        ->assertDontSee('window.Flux = {', false)
        ->assertDontSee(':root.dark', false);
})->with([
    'admin.dashboard',
    'profile.edit',
    'appearance.edit',
]);

test('auth layouts do not bootstrap dark mode', function () {
    $this
        ->get(route('login'))
        ->assertOk()
        ->assertDontSee('class="dark"', false)
        ->assertDontSee('window.Flux = {', false)
        ->assertDontSee(':root.dark', false);
});

test('appearance settings only expose light mode', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('Dark mode is disabled for this app')
        ->assertSee('value="light"', false)
        ->assertDontSee('value="dark"', false)
        ->assertDontSee('value="system"', false);
});

test('tailwind dark variants require an unused app opt-in selector', function () {
    expect(File::get(resource_path('css/app.css')))
        ->toContain('@custom-variant dark (&:where([data-dark-mode="enabled"], [data-dark-mode="enabled"] *));')
        ->toContain('color-scheme: light;');

    expect(File::get(resource_path('js/app.js')))
        ->toContain("root.classList.remove('dark')")
        ->toContain("window.localStorage.getItem('flux.appearance') !== 'light'")
        ->toContain('new MutationObserver(disableDarkMode)');
});
