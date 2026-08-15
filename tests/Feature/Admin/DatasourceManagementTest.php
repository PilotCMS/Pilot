<?php

use App\Livewire\Admin\Datasources\Index;
use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Database\Seeders\RoleSeeder;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders datasources for an authenticated editor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'CTA Styles',
        'slug' => 'cta-styles',
    ]);

    DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'primary',
        'value' => ['en' => 'Primary'],
        'order' => 0,
    ]);

    $this->actingAs($editor)
        ->get(route('admin.datasources.index'))
        ->assertOk()
        ->assertSee('Datasources')
        ->assertSee('CTA Styles')
        ->assertSee('cta-styles');
});

it('creates a datasource in the selected space', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $this->actingAs($editor);

    Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->call('openCreateDatasource')
        ->set('name', 'Theme Choices')
        ->set('slug', 'theme-choices')
        ->call('createDatasource')
        ->assertSet('showCreateModal', false);

    $datasource = Datasource::where('slug', 'theme-choices')->firstOrFail();

    expect($datasource->space_id)->toBe($space->id);
    expect($datasource->name)->toBe('Theme Choices');
});

it('updates selected datasource metadata', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    $this->actingAs($editor);

    Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->call('selectDatasource', $datasource->id)
        ->set('editName', 'New Name')
        ->set('editSlug', 'new-name')
        ->call('saveDatasource')
        ->assertHasNoErrors();

    $datasource->refresh();

    expect($datasource->name)->toBe('New Name');
    expect($datasource->slug)->toBe('new-name');
});

it('creates updates and deletes datasource entries', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Statuses',
        'slug' => 'statuses',
    ]);

    $this->actingAs($editor);

    $component = Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->call('selectDatasource', $datasource->id)
        ->set('newEntryKey', 'draft')
        ->set('newEntryValue', 'Draft')
        ->call('createEntry')
        ->assertHasNoErrors();

    $entry = DatasourceEntry::where('datasource_id', $datasource->id)->firstOrFail();

    expect($entry->key)->toBe('draft');
    expect($entry->value['en'])->toBe('Draft');

    $component
        ->call('editEntry', $entry->id)
        ->set('editEntryKey', 'published')
        ->set('editEntryValue', 'Published')
        ->call('saveEntry')
        ->assertSet('editingEntryId', null);

    $entry->refresh();

    expect($entry->key)->toBe('published');
    expect($entry->value['en'])->toBe('Published');

    $component->call('deleteEntry', $entry->id);

    expect(DatasourceEntry::whereKey($entry->id)->exists())->toBeFalse();
});

it('reorders datasource entries', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Styles',
        'slug' => 'styles',
    ]);

    $first = DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'primary',
        'value' => ['en' => 'Primary'],
        'order' => 0,
    ]);

    $second = DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'secondary',
        'value' => ['en' => 'Secondary'],
        'order' => 1,
    ]);

    $this->actingAs($editor);

    Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->call('selectDatasource', $datasource->id)
        ->call('moveEntryUp', $second->id);

    expect($second->fresh()->order)->toBe(0);
    expect($first->fresh()->order)->toBe(1);
});

it('prevents users without datasource management permission from mutating datasources', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $this->actingAs($viewer);

    Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->set('name', 'Blocked')
        ->set('slug', 'blocked')
        ->call('createDatasource')
        ->assertForbidden();

    expect(Datasource::where('slug', 'blocked')->exists())->toBeFalse();
});
