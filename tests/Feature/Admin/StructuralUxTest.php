<?php

use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Livewire\Admin\Content\Create;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Content\Index;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

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
