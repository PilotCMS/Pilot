<?php

use App\Models\User;
use App\Support\Installation\InstallationState;
use Illuminate\Support\Facades\File;
use Pilot\Core\Database\Seeders\DatabaseSeeder;
use Pilot\Core\Models\Space;

beforeEach(function () {
    $lock = storage_path('framework/testing/pilot-setup-wizard.json');
    File::delete($lock);

    config([
        'installation.assume_installed_when_testing' => false,
        'installation.lock_file' => $lock,
    ]);
});

test('an uninstalled application redirects browser traffic to setup', function () {
    $this->get('/')
        ->assertRedirect(route('setup.show'));

    $this->get('/setup')
        ->assertOk()
        ->assertSee('Let’s set up your Pilot workspace.')
        ->assertSee('Begin setup');
});

test('an uninstalled application returns a useful service response to API clients', function () {
    $this->getJson('/api/v1/spaces/main/contents')
        ->assertStatus(503)
        ->assertJsonPath('setup_url', route('setup.show'));
});

test('the requirements screen reports whether the server is ready', function () {
    $this->get('/setup/requirements')
        ->assertOk()
        ->assertSee('Everything Pilot needs to fly')
        ->assertSee('PHP 8.4.1 or newer')
        ->assertSee('Writable storage directory');
});

test('later steps cannot be skipped', function () {
    $this->get('/setup/account')
        ->assertRedirect(route('setup.show', ['step' => 'database']));
});

test('setup is no longer available after Pilot is installed', function () {
    app(InstallationState::class)->markInstalled(['administrator_id' => 1]);

    $this->get('/setup')->assertNotFound();
});

test('setup creates the first administrator and locks itself when finished', function () {
    app(DatabaseSeeder::class)->run();

    $this->withSession(['pilot_setup.database_ready' => true])
        ->post('/setup/account', [
            'name' => 'Pilot Owner',
            'email' => 'owner@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ])
        ->assertRedirect(route('setup.show', ['step' => 'project']));

    $admin = User::sole();

    expect($admin->hasRole('Admin'))->toBeTrue()
        ->and(Space::where('slug', 'main')->exists())->toBeTrue();

    $this->withSession(['pilot_setup.project' => ['app_name' => 'Pilot']]);

    $this->post('/setup/finish')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
    expect(app(InstallationState::class)->installed())->toBeTrue();
});
