<?php

use App\Livewire\Admin\Content\BlockEditor;
use App\Livewire\Admin\Content\ContentSyncPoller;
use App\Livewire\Admin\Content\Editor;
use App\Models\Block;
use App\Models\BlockType;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;
use Livewire\Livewire;

it('keeps block library state open until a block is inserted', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $firstBlock = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [],
    ]);

    $blockType = BlockType::create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => [
            'fields' => [],
        ],
        'is_global' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('addBlockAbove', $firstBlock->id)
        ->assertSet('blockLibraryOpen', true)
        ->call('addBlock', $blockType->key)
        ->assertSet('blockLibraryOpen', false)
        ->assertSet('selectedBlockId', Block::query()->latest('id')->value('id'));

    expect($content->blocks()->count())->toBe(2);
});

it('stores focal point metadata when selecting an asset for a block field', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'image',
        'position' => 0,
        'data' => [],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->call('handleAssetSelected', [
            'fieldKey' => 'image',
            'asset' => [
                'url' => 'http://localhost:8000/storage/assets/example.png',
                'focal_x' => 37.5,
                'focal_y' => 62.5,
            ],
        ]);

    $block->refresh();

    expect($block->data['image'])->toBe('/storage/assets/example.png');
    expect($block->data['image_focal_x'])->toBe(37.5);
    expect($block->data['image_focal_y'])->toBe(62.5);
});

it('updates a nested json object field in the cms block editor', function () {
    $blockType = BlockType::create([
        'key' => 'itinerary',
        'name' => 'Itinerary',
        'schema' => [
            'fields' => [
                [
                    'type' => 'repeater',
                    'key' => 'days',
                    'label' => 'Days',
                ],
            ],
        ],
        'is_global' => false,
    ]);

    $block = [
        'id' => 123,
        'type' => 'itinerary',
        'data' => [
            'days' => [
                [
                    'day' => '1',
                    'body' => 'Start at the north gate.',
                    'meta' => '90 mi',
                    'title' => 'Gardiner',
                ],
            ],
        ],
    ];

    Livewire::test(BlockEditor::class, [
        'block' => $block,
        'blockType' => $blockType,
    ])
        ->call('updateJsonObjectField', 'days', 0, 'title', 'Gardiner and Paradise Valley')
        ->assertSet('data.days.0.title', 'Gardiner and Paradise Valley')
        ->assertDispatched('block-updated');
});

it('moves top level blocks with the compose editor arrow controls', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $first = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [],
    ]);

    $second = Block::create([
        'content_id' => $content->id,
        'type' => 'cta',
        'position' => 1,
        'data' => [],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('moveBlockUp', $second->id)
        ->assertSet('blockLibraryOpen', false)
        ->assertSet('selectedBlockId', $second->id);

    expect($second->fresh()->position)->toBe(0)
        ->and($first->fresh()->position)->toBe(1);
});

it('moves nested blocks only within their current column', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $columns = Block::create([
        'content_id' => $content->id,
        'type' => 'columns',
        'position' => 0,
        'data' => ['columns' => 2],
    ]);

    $firstColumnFirst = Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'hero',
        'position' => 0,
        'data' => ['_column' => 0],
    ]);

    $firstColumnSecond = Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'cta',
        'position' => 1,
        'data' => ['_column' => 0],
    ]);

    $secondColumnBlock = Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'richtext',
        'position' => 0,
        'data' => ['_column' => 1],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('moveBlockUp', $firstColumnSecond->id)
        ->assertSet('blockLibraryOpen', false)
        ->assertSet('selectedBlockId', $firstColumnSecond->id);

    expect($firstColumnSecond->fresh()->position)->toBe(0)
        ->and($firstColumnFirst->fresh()->position)->toBe(1)
        ->and($secondColumnBlock->fresh()->position)->toBe(0);
});

it('opens the content fields panel when selecting a block from preview', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('drawerOpen', false)
        ->set('rightPanelTab', 'design')
        ->call('setSelectedBlockFromPreview', $block->id)
        ->assertSet('selectedBlockId', $block->id)
        ->assertSet('drawerOpen', true)
        ->assertSet('rightPanelTab', 'content');
});

it('refreshes selected block fields when content changes outside the editor', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => ['title' => 'Before'],
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->assertSet('blocks.0.data.title', 'Before')
        ->assertSet('previewVersion', 1)
        ->assertSet('editorSyncVersion', 1);

    $block->update(['data' => ['title' => 'After']]);
    $content->forceFill(['updated_at' => now()->addSeconds(5)])->save();

    $component
        ->call('syncExternalChanges')
        ->assertSet('blocks.0.data.title', 'After')
        ->assertSet('selectedBlockId', $block->id)
        ->assertSet('previewVersion', 2)
        ->assertSet('editorSyncVersion', 2);
});

it('polls for content changes without refreshing the full editor when nothing changed', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => ['title' => 'Before'],
    ]);

    $component = Livewire::test(ContentSyncPoller::class, [
        'contentId' => $content->id,
    ]);

    $component
        ->call('poll')
        ->assertNotDispatched('content-external-change-detected');

    $block->update(['data' => ['title' => 'After']]);

    $component
        ->call('poll')
        ->assertDispatched('content-external-change-detected');
});

it('can add a nested block inside a container block', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $columnsType = BlockType::create([
        'key' => 'columns',
        'name' => 'Columns',
        'schema' => [
            'fields' => [
                [
                    'type' => 'number',
                    'key' => 'columns',
                    'label' => 'Columns',
                    'default' => 2,
                ],
            ],
            'can_contain_blocks' => true,
        ],
        'is_global' => false,
    ]);

    $heroType = BlockType::create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => [
            'fields' => [
                [
                    'type' => 'text',
                    'key' => 'title',
                    'label' => 'Title',
                    'default' => 'Nested hero',
                ],
            ],
        ],
        'is_global' => false,
    ]);

    $parent = Block::create([
        'content_id' => $content->id,
        'type' => $columnsType->key,
        'position' => 0,
        'data' => ['columns' => 2],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('addNestedBlock', $parent->id, 1)
        ->assertSet('blockLibraryOpen', true)
        ->assertSet('addBlockParentId', $parent->id)
        ->assertSet('addBlockColumnIndex', 1)
        ->call('addBlock', $heroType->key)
        ->assertSet('blockLibraryOpen', false);

    $child = Block::query()->where('parent_block_id', $parent->id)->firstOrFail();

    expect($child->type)->toBe('hero')
        ->and($child->position)->toBe(0)
        ->and($child->data['title'])->toBe('Nested hero')
        ->and($child->data['_column'])->toBe(1);
});

it('restores nested blocks from a content revision', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $columns = Block::create([
        'content_id' => $content->id,
        'type' => 'columns',
        'position' => 0,
        'data' => ['columns' => 2],
    ]);

    Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => 'Nested CTA',
            '_column' => 1,
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('saveCheckpoint');

    $revision = $content->revisions()->firstOrFail();

    Block::where('content_id', $content->id)->delete();

    Livewire::test(Editor::class, ['content' => $content])
        ->call('restoreRevision', $revision->id);

    $restoredParent = Block::query()
        ->where('content_id', $content->id)
        ->whereNull('parent_block_id')
        ->firstOrFail();

    $restoredChild = Block::query()
        ->where('content_id', $content->id)
        ->where('parent_block_id', $restoredParent->id)
        ->firstOrFail();

    expect($restoredParent->type)->toBe('columns')
        ->and($restoredChild->type)->toBe('cta')
        ->and($restoredChild->data['title'])->toBe('Nested CTA')
        ->and($restoredChild->data['_column'])->toBe(1);
});
