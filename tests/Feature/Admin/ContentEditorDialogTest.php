<?php

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
