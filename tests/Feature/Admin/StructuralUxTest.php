<?php

use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Livewire\Admin\Activity\Index as ActivityIndex;
use Pilot\Core\Livewire\Admin\Blocks\Index as BlocksIndex;
use Pilot\Core\Livewire\Admin\Content\Create;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Content\Index;
use Pilot\Core\Livewire\Admin\Dashboard;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

it('opens the block folder modal without a Livewire request', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BlocksIndex::class)
        ->assertSeeHtml('x-on:click="newFolderModalOpen = true"')
        ->assertSeeHtml('x-show="newFolderModalOpen"')
        ->assertSeeHtml('x-on:click="newFolderModalOpen = false"')
        ->assertDontSeeHtml('wire:click="$set(\'showNewFolderModal\'');
});

it('uses a draft first progressively disclosed creation flow', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Website', 'slug' => 'website']);

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertSee('The URL slug is generated automatically.')
        ->assertSee('Advanced options')
        ->assertSee('New content starts as a draft.')
        ->assertDontSee('Configure your new content entry.')
        ->set('name', 'Summer Travel Guide')
        ->assertSet('slug', 'summer-travel-guide')
        ->set('status', 'published')
        ->call('save');

    $content = Content::query()->where('space_id', $space->id)->sole();

    expect($content->status)->toBe('draft')
        ->and($content->workflow_status)->toBe('draft')
        ->and($content->published_at)->toBeNull();

    $activity = Activity::query()->sole();

    expect($activity->action)->toBe('created')
        ->and($activity->subject->is($content))->toBeTrue()
        ->and($activity->meta['subject_name'])->toBe('Summer Travel Guide');
});

it('links page names from dashboard activity to their editors', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Website', 'slug' => 'website']);
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'type' => 'page',
        'name' => 'Summer Travel Guide',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    Activity::create([
        'space_id' => $space->id,
        'user_id' => $user->id,
        'action' => 'created',
        'subject_type' => Content::class,
        'subject_id' => $content->id,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Summer Travel Guide')
        ->assertSeeHtml('href="'.route('admin.content.editor', $content).'"')
        ->assertSeeHtml('href="'.route('admin.activity.index').'"');
});

it('shows a searchable detailed activity history', function () {
    $user = User::factory()->create(['name' => 'Jamie Editor']);
    $space = Space::create(['name' => 'Website', 'slug' => 'website']);
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'type' => 'page',
        'name' => 'Summer Travel Guide',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    Activity::create([
        'space_id' => $space->id,
        'user_id' => $user->id,
        'action' => 'published',
        'subject_type' => Content::class,
        'subject_id' => $content->id,
        'meta' => ['source' => 'editor'],
    ]);

    Livewire::actingAs($user)
        ->test(ActivityIndex::class)
        ->assertSee('Recent changes')
        ->assertSee('Jamie Editor')
        ->assertSee('Summer Travel Guide')
        ->assertSee('Source:')
        ->assertSee('editor')
        ->assertSeeHtml('datetime="')
        ->assertSeeHtml('href="'.route('admin.content.editor', $content).'"')
        ->set('search', 'does not exist')
        ->assertSee('No activity found')
        ->set('search', 'Summer Travel')
        ->assertSee('Summer Travel Guide')
        ->set('actionFilter', 'created')
        ->assertSee('No activity found');
});

it('keeps content activity contextual and removes inactive bulk selection', function () {
    $user = User::factory()->create();
    Space::create(['name' => 'Website', 'slug' => 'website']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Activity')
        ->assertSeeHtml('x-on:click="activityOpen = true"')
        ->assertDontSeeHtml('type="checkbox"');
});

it('renders semantic compose summaries instead of fallback implementation data', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Website', 'slug' => 'website']);
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    BlockType::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'icon' => 'photo',
        'is_global' => true,
    ]);
    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => [
            'title' => 'Explore summer',
            'description' => 'A concise author-facing summary.',
            'settings' => ['tracking_id' => 'internal-debug-value'],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSee('Explore summer')
        ->assertSee('A concise author-facing summary.')
        ->assertDontSee('Fallback preview')
        ->assertDontSeeHtml('<pre');
});
