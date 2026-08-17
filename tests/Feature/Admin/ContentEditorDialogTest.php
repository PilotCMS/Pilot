<?php

use App\Livewire\Admin\Content\BlockEditor;
use App\Livewire\Admin\Content\ContentSyncPoller;
use App\Livewire\Admin\Content\Editor;
use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Models\Activity;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockComment;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentPresence;
use Pilot\Core\Models\ContentRevision;
use Pilot\Core\Models\Space;
use Pilot\Core\Support\Cms\ContentLifecycle;
use Pilot\Core\Support\Cms\ContentRevisionInspector;
use Pilot\Core\Support\Cms\ContentSyncFingerprint;

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

it('does not render nonfunctional settings links in the content editor sidebar', function () {
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

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->assertDontSee('General')
        ->assertDontSee('Languages');
});

it('renders a searchable collapsible content tree with only the current parent expanded initially', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $currentFolder = Content::create([
        'space_id' => $space->id,
        'type' => 'folder',
        'slug' => 'guides',
        'name' => 'Guides',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $otherFolder = Content::create([
        'space_id' => $space->id,
        'type' => 'folder',
        'slug' => 'news',
        'name' => 'News',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'parent_id' => $currentFolder->id,
        'type' => 'page',
        'slug' => 'summer-guide',
        'name' => 'Summer Guide',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Content::create([
        'space_id' => $space->id,
        'parent_id' => $otherFolder->id,
        'type' => 'page',
        'slug' => 'company-news',
        'name' => 'Company News',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('admin.content.editor', $content));

    $response
        ->assertOk()
        ->assertSee('x-model.debounce.150ms="contentSearch"', false)
        ->assertSee('placeholder="Search pages"', false)
        ->assertSee("x-on:click=\"toggleFolder({$currentFolder->id})\"", false)
        ->assertSee("x-show=\"isFolderExpanded({$otherFolder->id})\"", false);

    expect($response->getContent())
        ->toMatch('/expandedFolderIds:\s+\['.$currentFolder->id.'\]/');
});

it('uses a conservative content sync polling interval', function () {
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

    $this->actingAs($user);

    $this->get(route('admin.content.editor', $content))
        ->assertOk()
        ->assertSee('wire:poll.10000ms="poll"', false)
        ->assertDontSee('wire:poll.5000ms="poll"', false)
        ->assertDontSee('wire:poll.1000ms="poll"', false);
});

it('stores normalized categories and tags for content', function () {
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

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->call('updateTaxonomy', 'categories', 'Travel, Destinations, travel')
        ->call('updateTaxonomy', 'tags', 'Hiking, summer, hiking')
        ->assertSee('Travel, Destinations')
        ->assertSee('Hiking, summer');

    $content->refresh();

    expect($content->categories)->toBe(['Travel', 'Destinations'])
        ->and($content->tags)->toBe(['Hiking', 'summer']);
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
        ->assertSet('editorSyncVersion', 1)
        ->call('handleAssetSelected', [
            'fieldKey' => 'image',
            'asset' => [
                'url' => 'http://localhost:8000/storage/assets/example.png',
                'focal_x' => 37.5,
                'focal_y' => 62.5,
            ],
        ])
        ->assertSet('editorSyncVersion', 1);

    $block->refresh();

    expect($block->data['image'])->toBe('/storage/assets/example.png');
    expect($block->data['image_focal_x'])->toBe(37.5);
    expect($block->data['image_focal_y'])->toBe(62.5);
});

it('keeps an external image url absolute when switching an image field', function () {
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
        'data' => ['image' => '/storage/assets/original.png'],
    ]);

    $externalUrl = 'https://images.example.com/replacement.jpg?width=1600';

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->call('handleAssetSelected', [
            'fieldKey' => 'image',
            'asset' => ['url' => $externalUrl],
        ]);

    expect($block->refresh()->data['image'])->toBe($externalUrl);
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

it('renders rich text fields with the wysiwyg editor', function () {
    $blockType = BlockType::create([
        'key' => 'richtext',
        'name' => 'Rich Text',
        'schema' => [
            'fields' => [
                [
                    'type' => 'richtext',
                    'key' => 'content',
                    'label' => 'Content',
                    'placeholder' => 'Start writing...',
                    'rows' => 7,
                ],
            ],
        ],
        'is_global' => false,
    ]);

    $block = [
        'id' => 123,
        'type' => 'richtext',
        'data' => [
            'content' => '<p>Already formatted</p>',
        ],
    ];

    Livewire::test(BlockEditor::class, [
        'block' => $block,
        'blockType' => $blockType,
    ])
        ->assertSeeHtml('class="pilot-richtext')
        ->assertSeeHtml('contenteditable="true"')
        ->assertSeeHtml('pilotRichTextEditor')
        ->assertSeeHtml('Expand rich text editor')
        ->assertSeeHtml("'is-expanded': expanded")
        ->assertDontSeeHtml('pilot-richtext-modal')
        ->assertSeeHtml('aria-label="Text formatting"')
        ->assertSee('Heading 6')
        ->assertDontSeeHtml('aria-label="Text color"')
        ->assertSeeHtml('data-lucide="align-left"')
        ->assertSeeHtml('data-lucide="code"');
});

it('expands schema repeater items and updates their nested fields', function () {
    $blockType = BlockType::create([
        'key' => 'gallery',
        'name' => 'Gallery',
        'schema' => [
            'fields' => [
                [
                    'type' => 'repeater',
                    'key' => 'images',
                    'label' => 'Images',
                    'fields' => [
                        [
                            'type' => 'image',
                            'key' => 'image',
                            'label' => 'Image',
                        ],
                        [
                            'type' => 'text',
                            'key' => 'caption',
                            'label' => 'Caption',
                            'translatable' => true,
                        ],
                    ],
                ],
            ],
        ],
        'is_global' => false,
    ]);

    $block = [
        'id' => 456,
        'type' => 'gallery',
        'data' => [
            'images' => [
                [
                    'image' => '/storage/assets/first.jpg',
                    'caption' => ['en' => 'First image'],
                ],
                [
                    'image' => '/storage/assets/second.jpg',
                    'caption' => ['en' => 'Second image'],
                ],
            ],
        ],
    ];

    Livewire::test(BlockEditor::class, [
        'block' => $block,
        'blockType' => $blockType,
    ])
        ->assertSee('Images 1')
        ->assertDontSee('Image URL')
        ->call('toggleRepeaterItem', 'images', 0)
        ->assertSet('expandedRepeaterItems.images.0', true)
        ->assertSee('Caption')
        ->assertSee('Image URL')
        ->call('updateRepeaterField', 'images', 0, 'caption', 'Updated caption')
        ->assertSet('data.images.0.caption.en', 'Updated caption')
        ->assertSet('expandedRepeaterItems.images.0', true)
        ->assertDispatched('block-updated')
        ->call('toggleRepeaterItem', 'images', 1)
        ->assertSet('expandedRepeaterItems.images.0', null)
        ->assertSet('expandedRepeaterItems.images.1', true)
        ->call('toggleRepeaterItem', 'images', 0)
        ->assertSet('expandedRepeaterItems.images.1', null)
        ->assertSet('expandedRepeaterItems.images.0', true)
        ->call('toggleRepeaterItem', 'images', 0)
        ->assertSet('expandedRepeaterItems.images.0', null);

    Livewire::test(BlockEditor::class, [
        'block' => $block,
        'blockType' => $blockType,
        'expandedRepeaterItems' => ['images' => [0 => true]],
    ])
        ->assertSet('expandedRepeaterItems.images.0', true)
        ->assertSee('Image URL');
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

it('shows the add block action on the preview panel', function () {
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

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->assertSee('x-show="canvasMode === \'preview\'"', false)
        ->assertSee('Add Block')
        ->assertSee('⌘B')
        ->assertSee('text-white', false)
        ->assertSee('text-sm font-medium text-white', false)
        ->assertSee("e.key.toLowerCase() === 'b'", false);
});

it('renders searchable block choices in the add block modal', function () {
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

    BlockType::create([
        'key' => 'testimonial',
        'name' => 'Testimonial',
        'schema' => ['description' => 'Customer quote'],
        'is_global' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('blockLibraryOpen', true)
        ->assertSee('Add a block...')
        ->assertSee('data-block-library', false)
        ->assertSee('data-lucide="box"', false)
        ->assertSee('No blocks match your search.')
        ->assertSee('data-block-search-text="testimonial testimonial customer quote"', false);
});

it('can collapse the editor side panels for a wider canvas', function () {
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

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->assertSee('Collapse pages')
        ->assertSee('Collapse inspector')
        ->assertSee('cms-drawer-header', false)
        ->assertSee('x-on:click="leftCollapsed = true"', false)
        ->assertSee('x-on:click="openPages()"', false)
        ->assertSee('Expand pages')
        ->assertDontSee('title="Open pages"', false)
        ->assertSee('x-on:click="inspectorOpen = false"', false)
        ->assertSee('x-on:click="openInspector()"', false)
        ->assertSee("marginRight: inspectorOpen ? 'var(--admin-rail-width)' : '44px'", false)
        ->assertSee("width: inspectorOpen ? 'var(--admin-rail-width)' : '44px'", false)
        ->assertSee('Expand inspector');
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

it('does not treat its own block field autosave as an external change', function () {
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

    $component = Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->call('updateBlock', $block->id, 'title', 'After')
        ->assertSet('saveState', 'saved')
        ->assertSet('conflictMessage', null);

    expect($component->get('lastKnownContentSyncKey'))
        ->toBe(ContentSyncFingerprint::make($content->fresh()));

    $component
        ->call('syncExternalChanges')
        ->assertSet('saveState', 'saved')
        ->assertSet('conflictMessage', null)
        ->assertSet('blocks.0.data.title', 'After');
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

it('creates a rollback checkpoint before restoring a revision', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Original page',
        'slug' => 'original-page',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => [
            'title' => 'Original hero',
            'subtitle' => 'Original subtitle',
        ],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Known good', $user->id);

    $content->update(['name' => 'Current draft']);
    $content->allBlocks()->delete();
    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'cta',
        'data' => ['title' => 'Current CTA'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('restoreRevision', $revision->id)
        ->assertHasNoErrors();

    $rollbackRevision = ContentRevision::query()
        ->where('content_id', $content->id)
        ->where('revision_type', 'pre_restore')
        ->where('source_revision_id', $revision->id)
        ->firstOrFail();

    expect($content->refresh()->name)->toBe('Original page')
        ->and($content->allBlocks()->first()->data['title'])->toBe('Original hero')
        ->and($rollbackRevision->label)->toBe('Before restore')
        ->and($rollbackRevision->snapshot['content']['name'])->toBe('Current draft')
        ->and($rollbackRevision->snapshot['blocks'][0]['data']['title'])->toBe('Current CTA')
        ->and(Activity::query()->where('action', 'restored revision')->where('subject_id', $content->id)->exists())->toBeTrue();
});

it('restores a revision created by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Original page',
        'slug' => 'original-page',
        'created_by' => $user->id,
        'updated_by' => $otherUser->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Other user hero'],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Other user checkpoint', $otherUser->id);

    $content->update(['name' => 'Current draft']);
    $content->allBlocks()->delete();
    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'cta',
        'data' => ['title' => 'Current CTA'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('selectRevision', $revision->id)
        ->assertSet('selectedRevisionId', $revision->id)
        ->call('restoreRevision', $revision->id)
        ->assertHasNoErrors();

    $rollbackRevision = ContentRevision::query()
        ->where('content_id', $content->id)
        ->where('revision_type', 'pre_restore')
        ->where('source_revision_id', $revision->id)
        ->firstOrFail();

    expect($content->refresh()->name)->toBe('Original page')
        ->and($content->updated_by)->toBe($user->id)
        ->and($content->allBlocks()->first()->data['title'])->toBe('Other user hero')
        ->and($rollbackRevision->user_id)->toBe($user->id)
        ->and($rollbackRevision->source_revision_id)->toBe($revision->id);
});

it('shows a revision comparison before restore', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Original page',
        'slug' => 'original-page',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Original hero'],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Known good', $user->id);

    $content->update([
        'name' => 'Current draft',
        'slug' => 'current-draft',
    ]);
    $content->allBlocks()->delete();
    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'cta',
        'data' => ['title' => 'Current CTA'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('selectRevision', $revision->id)
        ->assertSet('selectedRevisionId', $revision->id)
        ->assertSee('Changed fields')
        ->assertSee('Current draft')
        ->assertSee('Original page')
        ->assertSee('Block changes')
        ->assertSee('Hero')
        ->assertSee('Changed type, content')
        ->assertSee('Title')
        ->assertSee('Current CTA')
        ->assertSee('Original hero')
        ->assertSee('Comparing against Current draft')
        ->assertSee('Restoring fully will replace the current block tree');
});

it('compares revisions through the revision inspector service', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Current page',
        'created_by' => $user->id,
    ]);

    BlockType::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => [
            'fields' => [
                ['key' => 'title', 'label' => 'Title'],
            ],
        ],
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Revision hero'],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Hero checkpoint', $user->id);

    $block->update(['data' => ['title' => 'Current hero']]);
    $content->update(['name' => 'Changed page']);

    $comparison = app(ContentRevisionInspector::class)->compare(
        $content,
        $revision,
        blockTypes: BlockType::query()->get()->keyBy('key'),
    );

    expect($comparison['has_changes'])->toBeTrue()
        ->and($comparison['content_changes'][0]['label'])->toBe('Title')
        ->and($comparison['block_changes'][0]['label'])->toBe('Hero')
        ->and($comparison['block_changes'][0]['field_changes'][0]['label'])->toBe('Title')
        ->and($comparison['block_changes'][0]['field_changes'][0]['current'])->toBe('Current hero')
        ->and($comparison['block_changes'][0]['field_changes'][0]['revision'])->toBe('Revision hero');
});

it('creates named checkpoints from the revision panel', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->set('checkpointLabel', 'Before homepage rewrite')
        ->call('saveCheckpoint')
        ->assertHasNoErrors()
        ->assertSet('checkpointLabel', '');

    $revision = ContentRevision::query()->where('content_id', $content->id)->firstOrFail();

    expect($revision->label)->toBe('Before homepage rewrite')
        ->and($revision->revision_type)->toBe('manual')
        ->and($revision->user_id)->toBe($user->id);
});

it('shows changes since the published revision', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Published page',
        'slug' => 'published-page',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Published hero'],
    ]);

    $publishedRevision = app(ContentLifecycle::class)->publish($content, $user->id);

    $content->update([
        'name' => 'Draft page',
        'slug' => 'draft-page',
    ]);
    $block->update(['data' => ['title' => 'Draft hero']]);
    $content->touch();

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content->refresh()])
        ->assertSee('Since publish: 1 block, 2 fields')
        ->assertSeeHtml('role="tooltip"')
        ->assertSeeHtml('aria-describedby="since-publish-tooltip"')
        ->call('selectPublishedRevision')
        ->assertSet('selectedRevisionId', $publishedRevision->id)
        ->assertSet('revisionModalOpen', true)
        ->assertSee('Published page')
        ->assertSee('Draft page')
        ->assertSee('Block changes');
});

it('opens revisions and checkpoint workflows in a modal', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSet('revisionModalOpen', false)
        ->assertSeeHtml('aria-label="Revisions"')
        ->assertSeeHtml('title="Save checkpoint"')
        ->assertSee('Save')
        ->call('openRevisionModal')
        ->assertSet('revisionModalOpen', true)
        ->assertSee('Checkpoint label')
        ->assertSeeHtml('x-on:keydown.escape.window="$wire.closeRevisionModal()"')
        ->assertSeeHtml('class="fixed z-[60]')
        ->assertSeeHtml('-translate-y-1/2')
        ->assertSeeHtml('top: 3rem;')
        ->assertSeeHtml('right: max(1rem, calc((100vw - 72rem) / 2 + 1rem));')
        ->assertSee('esc')
        ->call('closeRevisionModal')
        ->assertSet('revisionModalOpen', false)
        ->call('openCheckpointModal')
        ->assertSet('revisionModalOpen', true)
        ->assertSee('Checkpoint label');
});

it('undoes the latest page edit from an auto checkpoint', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Home',
        'slug' => 'home',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSeeHtml('aria-label="Undo last change"')
        ->call('updateContent', 'name', 'Landing')
        ->call('undoLastChange');

    expect($content->refresh()->name)->toBe('Home')
        ->and($content->slug)->toBe('home')
        ->and($content->revisions()->where('revision_type', 'auto')->count())->toBe(0)
        ->and($content->revisions()->where('revision_type', 'pre_restore')->count())->toBe(1);
});

it('undoes repeated block edits in reverse checkpoint order', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Original hero'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('updateBlock', $block->id, 'title', 'First hero')
        ->call('updateBlock', $block->id, 'title', 'Second hero')
        ->call('undoLastChange');

    expect($content->refresh()->allBlocks()->firstOrFail()->data['title'])->toBe('First hero');

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content->refresh()])
        ->call('undoLastChange');

    expect($content->refresh()->allBlocks()->firstOrFail()->data['title'])->toBe('Original hero')
        ->and($content->revisions()->where('revision_type', 'auto')->count())->toBe(0);
});

it('filters revisions and loads more history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);

    foreach (range(1, 22) as $index) {
        app(ContentLifecycle::class)->createRevision($content, 'Checkpoint '.str_pad((string) $index, 3, '0', STR_PAD_LEFT), $user->id, 'manual');
    }

    app(ContentLifecycle::class)->createRevision($content, 'Published checkpoint', $otherUser->id, 'published');

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('openRevisionModal')
        ->assertSee('Checkpoint 022')
        ->assertDontSee('Checkpoint 001')
        ->call('loadMoreRevisions')
        ->assertSee('Checkpoint 001')
        ->set('revisionTypeFilter', 'published')
        ->assertSee('Published checkpoint')
        ->assertDontSee('Checkpoint 022')
        ->set('revisionTypeFilter', '')
        ->set('revisionAuthorFilter', (string) $otherUser->id)
        ->assertSee('Published checkpoint')
        ->assertDontSee('Checkpoint 022');
});

it('compares a selected revision against another revision', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'First title',
        'created_by' => $user->id,
    ]);

    $firstRevision = app(ContentLifecycle::class)->createRevision($content, 'First checkpoint', $user->id);

    $content->update(['name' => 'Second title']);
    $secondRevision = app(ContentLifecycle::class)->createRevision($content, 'Second checkpoint', $user->id);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('selectRevision', $firstRevision->id)
        ->set('compareRevisionId', (string) $secondRevision->id)
        ->assertSee('Comparing against Second checkpoint')
        ->assertSee('First title')
        ->assertSee('Second title');
});

it('selectively restores page fields and individual blocks from a revision', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Original title',
        'slug' => 'original-title',
        'created_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Original hero'],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Original checkpoint', $user->id);

    $content->update([
        'name' => 'Draft title',
        'slug' => 'draft-title',
    ]);
    $content->allBlocks()->first()->update(['data' => ['title' => 'Draft hero']]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('selectRevision', $revision->id)
        ->call('restoreSelectedRevisionContent')
        ->assertHasNoErrors();

    expect($content->refresh()->name)->toBe('Original title')
        ->and($content->allBlocks()->first()->data['title'])->toBe('Draft hero');

    $content->update(['name' => 'Draft title']);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content->refresh()])
        ->call('selectRevision', $revision->id)
        ->call('restoreSelectedRevisionBlock', '1')
        ->assertHasNoErrors();

    expect($content->refresh()->name)->toBe('Draft title')
        ->and($content->allBlocks()->first()->data['title'])->toBe('Original hero');
});

it('selectively restores nested blocks from a revision path', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);

    $columns = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'columns',
        'data' => ['columns' => 2],
    ]);

    $nestedBlock = Block::factory()->create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'cta',
        'data' => [
            'title' => 'Original nested CTA',
            '_column' => 0,
        ],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Nested checkpoint', $user->id);

    $nestedBlock->update([
        'data' => [
            'title' => 'Changed nested CTA',
            '_column' => 0,
        ],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('selectRevision', $revision->id)
        ->call('restoreSelectedRevisionBlock', '1.1')
        ->assertHasNoErrors();

    $restoredNestedBlock = Block::query()
        ->where('content_id', $content->id)
        ->whereNotNull('parent_block_id')
        ->firstOrFail();

    expect($restoredNestedBlock->data['title'])->toBe('Original nested CTA')
        ->and($restoredNestedBlock->parent_block_id)->toBe($columns->id);
});

it('creates deduped automatic checkpoints before risky operations', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    $block = Block::factory()->create(['content_id' => $content->id]);

    app(ContentLifecycle::class)->createRevisionIfChanged($content, 'Initial auto checkpoint', $user->id, 'auto');
    app(ContentLifecycle::class)->createRevisionIfChanged($content, 'Duplicate auto checkpoint', $user->id, 'auto');

    expect(ContentRevision::query()->where('content_id', $content->id)->where('revision_type', 'auto')->count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('deleteBlock', $block->id)
        ->assertHasNoErrors();

    expect(ContentRevision::query()->where('content_id', $content->id)->where('revision_type', 'auto')->count())->toBe(1);
});

it('deletes a container block with its nested block tree', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    $container = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'columns',
        'position' => 0,
    ]);
    $child = Block::factory()->create([
        'content_id' => $content->id,
        'parent_block_id' => $container->id,
        'position' => 0,
    ]);
    $grandchild = Block::factory()->create([
        'content_id' => $content->id,
        'parent_block_id' => $child->id,
        'position' => 0,
    ]);
    $remainingBlock = Block::factory()->create([
        'content_id' => $content->id,
        'position' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('deleteBlock', $container->id)
        ->assertHasNoErrors()
        ->assertSet('selectedBlockId', $remainingBlock->id);

    expect(Block::query()->whereKey([$container->id, $child->id, $grandchild->id])->exists())->toBeFalse()
        ->and($remainingBlock->refresh()->position)->toBe(0)
        ->and($content->refresh()->updated_by)->toBe($user->id);
});

it('prunes old automatic revisions by retention limit', function () {
    config(['cms.auto_revision_retention' => 3]);

    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    Block::factory()->create(['content_id' => $content->id]);

    foreach (range(1, 5) as $index) {
        $content->update(['name' => 'Auto checkpoint '.$index]);
        app(ContentLifecycle::class)->createRevisionIfChanged($content->refresh(), 'Auto '.$index, $user->id, 'auto');
    }

    $autoRevisions = ContentRevision::query()
        ->where('content_id', $content->id)
        ->where('revision_type', 'auto')
        ->latest()
        ->get();

    expect($autoRevisions)->toHaveCount(3)
        ->and($autoRevisions->pluck('label')->all())->toBe(['Auto 5', 'Auto 4', 'Auto 3']);
});

it('restores reusable block metadata from a content revision', function () {
    $user = User::factory()->create();
    $sourceContent = Content::factory()->create(['created_by' => $user->id]);
    $content = Content::factory()->create(['created_by' => $user->id]);

    $sourceBlock = Block::factory()->create([
        'content_id' => $sourceContent->id,
        'type' => 'hero',
        'reusable_key' => 'global-hero',
        'reusable_name' => 'Global Hero',
        'data' => ['title' => 'Global'],
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'reusable_source_block_id' => $sourceBlock->id,
        'type' => 'hero',
        'reusable_key' => 'global-hero',
        'reusable_name' => 'Global Hero',
        'data' => ['title' => 'Instance'],
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Reusable checkpoint', $user->id);

    $content->allBlocks()->delete();

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('restoreRevision', $revision->id)
        ->assertHasNoErrors();

    $restoredBlock = $content->allBlocks()->firstOrFail();

    expect($restoredBlock->reusable_source_block_id)->toBe($sourceBlock->id)
        ->and($restoredBlock->reusable_key)->toBe('global-hero')
        ->and($restoredBlock->reusable_name)->toBe('Global Hero')
        ->and($restoredBlock->data['title'])->toBe('Instance');
});

it('tracks editor presence and selected block context', function () {
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
        ->set('selectedBlockId', $block->id)
        ->call('touchPresence');

    $presence = ContentPresence::query()->where('content_id', $content->id)->where('user_id', $user->id)->firstOrFail();

    expect($presence->selected_block_id)->toBe($block->id)
        ->and($presence->status)->toBe('editing');
});

it('clears stale selected block presence after undo restores block snapshots', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Original hero'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->call('touchPresence')
        ->call('updateBlock', $block->id, 'title', 'Edited hero')
        ->call('undoLastChange')
        ->call('touchPresence')
        ->assertSet('selectedBlockId', null);

    $presence = ContentPresence::query()->where('content_id', $content->id)->where('user_id', $user->id)->firstOrFail();

    expect($presence->selected_block_id)->toBeNull()
        ->and($presence->status)->toBe('viewing');
});

it('adds and resolves comments for a selected block', function () {
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
        ->set('selectedBlockId', $block->id)
        ->set('newCommentBody', 'Tighten the hero copy.')
        ->call('addBlockComment')
        ->assertSet('newCommentBody', '');

    $comment = BlockComment::query()->firstOrFail();

    expect($comment->block_id)->toBe($block->id)
        ->and($comment->body)->toBe('Tighten the hero copy.')
        ->and($comment->resolved_at)->toBeNull();

    Livewire::test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $block->id)
        ->call('resolveBlockComment', $comment->id);

    expect($comment->fresh()->resolved_at)->not->toBeNull();
});

it('assigns and approves a content review', function () {
    $author = User::factory()->create();
    $reviewer = User::factory()->create();
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
        'created_by' => $author->id,
        'updated_by' => $author->id,
    ]);

    $this->actingAs($author);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('reviewerId', (string) $reviewer->id)
        ->set('reviewDueAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('reviewNote', 'Please review the launch copy.')
        ->call('assignReview');

    $content->refresh();

    expect($content->workflow_status)->toBe('in_review')
        ->and($content->reviewer_id)->toBe($reviewer->id)
        ->and($content->review_note)->toBe('Please review the launch copy.');

    Livewire::test(Editor::class, ['content' => $content])
        ->call('approveReview');

    expect($content->fresh()->workflow_status)->toBe('approved');
});

it('reports validation issues for required block fields', function () {
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

    BlockType::create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => [
            'fields' => [
                [
                    'type' => 'text',
                    'key' => 'headline',
                    'label' => 'Headline',
                    'required' => true,
                ],
            ],
        ],
        'is_global' => false,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->assertSee('Hero is missing Headline.');
});

it('creates reusable blocks and syncs inserted instances when the source changes', function () {
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

    $source = Block::create([
        'content_id' => $content->id,
        'type' => 'cta',
        'position' => 0,
        'data' => ['title' => 'Original CTA'],
    ]);

    $this->actingAs($user);

    Livewire::test(Editor::class, ['content' => $content])
        ->set('selectedBlockId', $source->id)
        ->set('reusableBlockName', 'Campaign CTA')
        ->call('makeSelectedBlockReusable')
        ->call('insertReusableBlock', $source->id);

    $source->refresh();
    $instance = Block::query()->where('reusable_source_block_id', $source->id)->firstOrFail();

    expect($source->reusable_name)->toBe('Campaign CTA')
        ->and($instance->data['title'])->toBe('Original CTA');

    Livewire::test(Editor::class, ['content' => $content])
        ->call('updateBlock', $source->id, 'title', 'Updated CTA');

    expect($instance->fresh()->data['title'])->toBe('Updated CTA');
});
