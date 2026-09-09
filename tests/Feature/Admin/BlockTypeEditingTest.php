<?php

use Livewire\Livewire;
use Pilot\Core\Livewire\Admin\Blocks\Edit;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;

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

it('configures content type field mappings for a content collection', function () {
    ContentType::factory()->create([
        'name' => 'Itinerary',
        'key' => 'itinerary',
        'schema' => ['fields' => [
            ['key' => 'summary', 'type' => 'textarea', 'label' => 'Summary'],
        ]],
    ]);
    $blockType = BlockType::factory()->create(['key' => 'itinerary-cards']);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->call('addFieldOfType', 'content_collection')
        ->set('schema.fields.0.key', 'cards')
        ->set('schema.fields.0.label', 'Cards')
        ->set('schema.fields.0.source_content_type', 'itinerary')
        ->set('schema.fields.0.mappings.0.target', 'title')
        ->set('schema.fields.0.mappings.0.source', 'name')
        ->call('addMapping', 0)
        ->set('schema.fields.0.mappings.1.target', 'body')
        ->set('schema.fields.0.mappings.1.source', 'meta.summary')
        ->assertSee('Content source and mapping')
        ->assertSee('Itinerary')
        ->call('save')
        ->assertHasNoErrors();

    expect($blockType->fresh()->schema['fields'][0])
        ->toMatchArray([
            'type' => 'content_collection',
            'key' => 'cards',
            'source_content_type' => 'itinerary',
            'mappings' => [
                ['target' => 'title', 'source' => 'name'],
                ['target' => 'body', 'source' => 'meta.summary'],
            ],
        ]);
});

it('configures a multiselect field with option values', function () {
    $blockType = BlockType::factory()->create(['key' => 'article-card']);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->call('addFieldOfType', 'multiselect')
        ->assertSet('schema.fields.0.default', [])
        ->assertSet('schema.fields.0.options.0', ['value' => '', 'label' => ''])
        ->set('schema.fields.0.key', 'topics')
        ->set('schema.fields.0.label', 'Topics')
        ->set('schema.fields.0.options.0.value', 'design')
        ->set('schema.fields.0.options.0.label', 'Design')
        ->call('addOption', 0)
        ->set('schema.fields.0.options.1.value', 'engineering')
        ->set('schema.fields.0.options.1.label', 'Engineering')
        ->assertSee('Combobox / Multiselect')
        ->call('save')
        ->assertHasNoErrors();

    expect($blockType->fresh()->schema['fields'][0])
        ->toMatchArray([
            'type' => 'multiselect',
            'key' => 'topics',
            'default' => [],
            'options' => [
                ['value' => 'design', 'label' => 'Design'],
                ['value' => 'engineering', 'label' => 'Engineering'],
            ],
        ]);
});

it('normalizes defaults when changing an existing field to multiselect', function () {
    $blockType = BlockType::factory()->create([
        'schema' => ['fields' => [[
            'type' => 'text',
            'key' => 'topics',
            'label' => 'Topics',
            'default' => 'featured',
        ]]],
    ]);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->set('schema.fields.0.type', 'multiselect')
        ->assertSet('schema.fields.0.default', [])
        ->assertSet('schema.fields.0.options.0', ['value' => '', 'label' => '']);
});

it('selects a datasource as the option source for option fields', function () {
    $space = Space::factory()->create(['name' => 'Website']);
    $datasource = Datasource::create([
        'space_id' => $space->id,
        'name' => 'Topics',
        'slug' => 'topics',
    ]);
    DatasourceEntry::create([
        'datasource_id' => $datasource->id,
        'key' => 'design',
        'value' => ['en' => 'Design'],
        'order' => 0,
    ]);
    $blockType = BlockType::factory()->create(['key' => 'article-card']);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->call('addFieldOfType', 'multiselect')
        ->set('schema.fields.0.key', 'topics')
        ->set('schema.fields.0.option_source', 'datasource')
        ->assertSee('Choose a datasource')
        ->assertSee('Topics (topics)')
        ->set('schema.fields.0.datasource', 'topics')
        ->call('save')
        ->assertHasNoErrors();

    expect($blockType->fresh()->schema['fields'][0])
        ->toMatchArray([
            'type' => 'multiselect',
            'option_source' => 'datasource',
            'datasource' => 'topics',
        ]);
});

it('preserves datasource-backed option fields from older schemas', function () {
    $blockType = BlockType::factory()->create([
        'schema' => ['fields' => [[
            'type' => 'select',
            'key' => 'topic',
            'label' => 'Topic',
            'datasource' => 'topics',
        ]]],
    ]);

    Livewire::test(Edit::class, ['blockType' => $blockType])
        ->assertSet('schema.fields.0.option_source', 'datasource')
        ->assertSet('schema.fields.0.datasource', 'topics');
});
