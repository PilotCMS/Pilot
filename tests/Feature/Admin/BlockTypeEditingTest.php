<?php

use App\Livewire\Admin\Blocks\Edit;
use Livewire\Livewire;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;

it('updates block relationships when a block type key changes', function () {
    $blockType = BlockType::factory()->create(['key' => 'old-key']);
    $content = Content::factory()->create();
    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'old-key',
        'position' => 0,
        'data' => [],
    ]);
    $allowedContentType = ContentType::factory()->create([
        'allowed_blocks' => ['hero', 'old-key', 'cta'],
    ]);
    $unrelatedContentType = ContentType::factory()->create([
        'allowed_blocks' => ['hero'],
    ]);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->set('key', 'new-key')
        ->call('save')
        ->assertHasNoErrors();

    expect($blockType->fresh()->key)->toBe('new-key')
        ->and($block->fresh()->type)->toBe('new-key')
        ->and($block->fresh()->blockType->is($blockType))->toBeTrue()
        ->and($allowedContentType->fresh()->allowed_blocks)->toBe(['hero', 'new-key', 'cta'])
        ->and($unrelatedContentType->fresh()->allowed_blocks)->toBe(['hero']);
});

it('requires an edited block type key to remain unique', function () {
    $blockType = BlockType::factory()->create(['key' => 'original-key']);
    BlockType::factory()->create(['key' => 'existing-key']);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->set('key', 'existing-key')
        ->call('save')
        ->assertHasErrors(['key' => 'unique']);

    expect($blockType->fresh()->key)->toBe('original-key');
});

it('shows a danger warning beside the editable block key', function () {
    $blockType = BlockType::factory()->create(['key' => 'hero']);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->assertSee('Danger: changing the block key can cause errors.')
        ->assertSee('relationships will update automatically');
});
