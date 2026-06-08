<?php

use App\Livewire\Admin\Users\Index;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admins to view users and roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $editor = User::factory()->create([
        'name' => 'Editor User',
        'email' => 'editor@example.com',
    ]);
    $editor->assignRole('Editor');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Users')
        ->assertSee('Editor User')
        ->assertSee('Editor');
});

it('prevents non admins from viewing the users area', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $this->actingAs($author)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('creates a user with the selected role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('openCreateUser')
        ->set('name', 'Morgan Editor')
        ->set('email', 'morgan@example.com')
        ->set('password', 'password')
        ->set('roleName', 'Editor')
        ->call('createUser')
        ->assertSet('showCreateModal', false);

    $user = User::where('email', 'morgan@example.com')->firstOrFail();

    expect($user->name)->toBe('Morgan Editor');
    expect($user->hasRole('Editor'))->toBeTrue();
});

it('updates a selected user profile and role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $user->assignRole('Author');

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('selectUser', $user->id)
        ->set('editName', 'Updated Name')
        ->set('editEmail', 'updated@example.com')
        ->set('editRoleName', 'Editor')
        ->call('updateSelectedUser');

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->hasRole('Editor'))->toBeTrue();
    expect($user->hasRole('Author'))->toBeFalse();
});

it('does not allow admins to delete themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('selectUser', $admin->id)
        ->call('deleteSelectedUser')
        ->assertHasErrors(['selectedUserId']);

    expect(User::whereKey($admin->id)->exists())->toBeTrue();
});

it('deletes another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create();
    $user->assignRole('Viewer');

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('deleteUser', $user->id)
        ->assertSet('selectedUserId', null);

    expect(User::whereKey($user->id)->exists())->toBeFalse();
});

it('shows role permission summaries in the details rail', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->assertSee(Role::where('name', 'Admin')->firstOrFail()->permissions->first()->name)
        ->assertSee('manage users');
});
