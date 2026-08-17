<?php

use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Livewire\Admin\CommandPalette;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders quick links in the admin layout', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeLivewire(CommandPalette::class)
        ->assertSee('Search')
        ->assertSee('⌘K');
});

it('finds content records from the command palette', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $content = Content::factory()->create([
        'name' => 'Summer Launch Page',
        'slug' => 'summer-launch',
    ]);

    $this->actingAs($admin);

    Livewire::test(CommandPalette::class)
        ->set('search', 'summer')
        ->assertSee('Summer Launch Page')
        ->assertSee(route('admin.content.edit', $content));
});

it('hides admin only results from non admins', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    BlockType::factory()->create([
        'name' => 'Marketing Hero',
        'key' => 'marketing-hero',
    ]);

    $this->actingAs($author);

    Livewire::test(CommandPalette::class)
        ->set('search', 'marketing')
        ->assertDontSee('Marketing Hero')
        ->assertDontSee(route('admin.blocks.index'));
});
