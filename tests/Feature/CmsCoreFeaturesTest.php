<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Pilot\Core\Livewire\Admin\Content\BlockEditor;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Spaces\Index as SpacesIndex;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentReference;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Redirect;
use Pilot\Core\Models\Space;
use Pilot\Core\Support\Cms\ContentCollectionResolver;
use Pilot\Core\Support\Cms\ContentLifecycle;

it('creates content with a content type and exposes the type in delivery payloads', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create(['slug' => 'website']);
    $contentType = ContentType::factory()->create([
        'name' => 'Landing Page',
        'key' => 'landing-page',
    ]);

    $content = Content::factory()->published()->create([
        'space_id' => $space->id,
        'content_type_id' => $contentType->id,
        'slug' => 'home',
        'name' => 'Home',
        'created_by' => $user->id,
    ]);

    $this->getJson('/api/v1/spaces/website/contents/home')
        ->assertOk()
        ->assertJsonPath('story.content_type', 'landing-page')
        ->assertJsonPath('story.name', $content->name);
});

it('creates redirects when content slugs change', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->published()->create([
        'space_id' => $space->id,
        'slug' => 'old-page',
        'name' => 'Old Page',
        'created_by' => $user->id,
    ]);

    app(ContentLifecycle::class)->updateContent($content, ['slug' => 'new-page'], $user->id);

    expect(Redirect::query()->where('source', '/old-page')->where('destination', '/new-page')->exists())->toBeTrue();
});

it('handles review, scheduled publishing, and revision restore', function () {
    Carbon::setTestNow('2026-06-09 12:00:00');

    $user = User::factory()->create();
    $content = Content::factory()->create([
        'slug' => 'workflow-page',
        'name' => 'Workflow Page',
        'created_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Original'],
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('requestReview')
        ->assertHasNoErrors();

    expect($content->refresh()->workflow_status)->toBe('in_review');

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->set('scheduledFor', '2026-06-09T12:10')
        ->call('schedulePublishing')
        ->assertHasNoErrors();

    expect($content->refresh()->workflow_status)->toBe('scheduled')
        ->and($content->scheduled_for?->format('Y-m-d H:i'))->toBe('2026-06-09 12:10');

    Carbon::setTestNow('2026-06-09 12:11:00');

    $this->artisan('pilot:publish-scheduled')
        ->assertSuccessful();

    expect($content->refresh()->status)->toBe('published')
        ->and($content->workflow_status)->toBe('published')
        ->and($content->published_revision_id)->not->toBeNull();

    $revision = $content->revisions()->firstOrFail();
    $content->update(['name' => 'Changed']);
    $content->allBlocks()->delete();

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->call('restoreRevision', $revision->id)
        ->assertHasNoErrors();

    expect($content->refresh()->name)->toBe('Workflow Page')
        ->and($content->allBlocks()->count())->toBe(1);

    Carbon::setTestNow();
});

it('indexes reference fields from block schemas', function () {
    $user = User::factory()->create();
    $source = Content::factory()->create(['created_by' => $user->id]);
    $target = Content::factory()->create(['created_by' => $user->id]);

    BlockType::factory()->create([
        'key' => 'related-card',
        'schema' => [
            'fields' => [
                [
                    'type' => 'reference',
                    'key' => 'related_entry',
                    'label' => 'Related Entry',
                ],
            ],
        ],
    ]);

    $block = Block::factory()->create([
        'content_id' => $source->id,
        'type' => 'related-card',
        'data' => ['related_entry' => $target->id],
    ]);

    Livewire::actingAs($user)
        ->test(BlockEditor::class, [
            'block' => $block->toArray(),
            'blockType' => BlockType::where('key', 'related-card')->first(),
        ])
        ->call('updateField', 'related_entry', $target->id);

    app(ContentLifecycle::class)->syncReferences($source);

    expect(ContentReference::query()
        ->where('content_id', $source->id)
        ->where('target_content_id', $target->id)
        ->where('field_key', 'related_entry')
        ->exists())->toBeTrue();
});

it('suggests internal page links for link-like block fields', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create([
        'name' => 'Home',
        'slug' => 'home',
        'created_by' => $user->id,
    ]);
    Content::factory()->create([
        'name' => 'About',
        'slug' => 'about',
        'created_by' => $user->id,
    ]);

    $blockType = BlockType::factory()->create([
        'key' => 'cta-link',
        'schema' => [
            'fields' => [
                [
                    'type' => 'text',
                    'key' => 'button_url',
                    'label' => 'Button URL',
                ],
            ],
        ],
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'cta-link',
        'data' => ['button_url' => ''],
    ]);

    Livewire::actingAs($user)
        ->test(BlockEditor::class, [
            'block' => $block->toArray(),
            'blockType' => $blockType,
        ])
        ->assertSeeHtml('list="internal-links-button-url"')
        ->assertSee('About /about')
        ->call('updateField', 'button_url', '/about')
        ->assertSet('data.button_url', '/about')
        ->call('updateField', 'button_url', 'https://example.com/custom')
        ->assertSet('data.button_url', 'https://example.com/custom');
});

it('suggests internal page links for repeater link fields', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    Content::factory()->create([
        'name' => 'Pricing',
        'slug' => 'pricing',
        'created_by' => $user->id,
    ]);

    $blockType = BlockType::factory()->create([
        'key' => 'nav-links',
        'schema' => [
            'fields' => [
                [
                    'type' => 'repeater',
                    'key' => 'links',
                    'label' => 'Links',
                    'fields' => [
                        [
                            'type' => 'text',
                            'key' => 'label',
                            'label' => 'Label',
                        ],
                        [
                            'type' => 'text',
                            'key' => 'href',
                            'label' => 'Href',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'nav-links',
        'data' => [
            'links' => [
                [
                    'label' => 'Plans',
                    'href' => '',
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(BlockEditor::class, [
            'block' => $block->toArray(),
            'blockType' => $blockType,
        ])
        ->call('toggleRepeaterItem', 'links', 0)
        ->assertSeeHtml('list="internal-links-links-href-0"')
        ->assertSee('Pricing /pricing')
        ->call('updateRepeaterField', 'links', 0, 'href', '/pricing')
        ->assertSet('data.links.0.href', '/pricing')
        ->call('updateRepeaterField', 'links', 0, 'href', 'mailto:sales@example.com')
        ->assertSet('data.links.0.href', 'mailto:sales@example.com');
});

it('resolves mapped content collections with taxonomy filters ordering and limits', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $pageType = ContentType::factory()->create(['key' => 'page']);
    $itineraryType = ContentType::factory()->create([
        'key' => 'itinerary',
        'schema' => ['fields' => [
            ['key' => 'summary', 'type' => 'textarea', 'label' => 'Summary'],
            ['key' => 'cover_image', 'type' => 'image', 'label' => 'Cover image'],
        ]],
    ]);
    $owner = Content::factory()->published()->create([
        'space_id' => $space->id,
        'content_type_id' => $pageType->id,
        'created_by' => $user->id,
    ]);

    foreach ([
        ['name' => 'Yellowstone Loop', 'slug' => 'yellowstone-loop', 'tags' => ['summer'], 'categories' => ['Road Trips']],
        ['name' => 'Glacier Family Week', 'slug' => 'glacier-family-week', 'tags' => ['summer', 'family'], 'categories' => ['Road Trips']],
        ['name' => 'Winter Powder', 'slug' => 'winter-powder', 'tags' => ['winter'], 'categories' => ['Road Trips']],
    ] as $itinerary) {
        Content::factory()->published()->create([
            'space_id' => $space->id,
            'content_type_id' => $itineraryType->id,
            'name' => $itinerary['name'],
            'slug' => $itinerary['slug'],
            'categories' => $itinerary['categories'],
            'tags' => $itinerary['tags'],
            'meta' => [
                'summary' => ['en' => $itinerary['name'].' summary'],
                'cover_image' => '/images/'.$itinerary['slug'].'.jpg',
            ],
            'created_by' => $user->id,
        ]);
    }

    Content::factory()->create([
        'space_id' => $space->id,
        'content_type_id' => $itineraryType->id,
        'name' => 'Draft itinerary',
        'tags' => ['summer'],
        'created_by' => $user->id,
    ]);

    $items = app(ContentCollectionResolver::class)->resolve($owner, [
        'type' => 'content_collection',
        'source_content_type' => 'itinerary',
        'mappings' => [
            ['target' => 'title', 'source' => 'name'],
            ['target' => 'body', 'source' => 'meta.summary'],
            ['target' => 'image', 'source' => 'meta.cover_image'],
            ['target' => 'url', 'source' => '$url'],
            ['target' => 'kicker', 'source' => 'categories.0'],
        ],
    ], [
        'categories' => ['Road Trips'],
        'tags' => ['summer'],
        'limit' => 2,
        'order_by' => 'name',
        'order_direction' => 'asc',
    ], 'en');

    expect($items)->toHaveCount(2)
        ->and(array_column($items, 'title'))->toBe(['Glacier Family Week', 'Yellowstone Loop'])
        ->and($items[0]['body'])->toBe('Glacier Family Week summary')
        ->and($items[0]['url'])->toBe('/glacier-family-week')
        ->and($items[0]['kicker'])->toBe('Road Trips');

    $blockType = BlockType::factory()->create([
        'key' => 'itinerary-cards',
        'schema' => ['fields' => [[
            'type' => 'content_collection',
            'key' => 'cards',
            'source_content_type' => 'itinerary',
            'mappings' => [
                ['target' => 'title', 'source' => 'name'],
                ['target' => 'url', 'source' => '$url'],
            ],
        ]]],
    ]);
    Block::factory()->create([
        'content_id' => $owner->id,
        'type' => $blockType->key,
        'data' => ['cards' => [
            'categories' => ['Road Trips'],
            'tags' => ['summer'],
            'limit' => 1,
            'order_by' => 'name',
            'order_direction' => 'asc',
        ]],
    ]);

    $this->getJson('/api/v1/spaces/'.$space->slug.'/contents/'.$owner->slug)
        ->assertOk()
        ->assertJsonPath('story.body.0.data.cards.0.title', 'Glacier Family Week')
        ->assertJsonPath('story.body.0.data.cards.0.url', '/glacier-family-week')
        ->assertJsonPath('story.body.0.data._content_collections.cards.limit', 1);
});

it('edits content collection query controls on a component instance', function () {
    $user = User::factory()->create();
    $content = Content::factory()->create(['created_by' => $user->id]);
    $blockType = BlockType::factory()->create([
        'key' => 'dynamic-cards',
        'schema' => ['fields' => [[
            'type' => 'content_collection',
            'key' => 'cards',
            'label' => 'Cards',
            'source_content_type' => 'itinerary',
            'mappings' => [['target' => 'title', 'source' => 'name']],
        ]]],
    ]);
    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => $blockType->key,
        'data' => ['cards' => []],
    ]);

    Livewire::actingAs($user)
        ->test(BlockEditor::class, ['block' => $block->toArray(), 'blockType' => $blockType])
        ->assertSee('Dynamic')
        ->call('updateContentCollectionOption', 'cards', 'categories', 'Road Trips, Family, Road Trips')
        ->call('updateContentCollectionOption', 'cards', 'tags', 'summer, scenic')
        ->call('updateContentCollectionOption', 'cards', 'limit', 200)
        ->call('updateContentCollectionOption', 'cards', 'order_by', 'name')
        ->call('updateContentCollectionOption', 'cards', 'order_direction', 'asc')
        ->assertSet('data.cards.categories', ['Road Trips', 'Family'])
        ->assertSet('data.cards.tags', ['summer', 'scenic'])
        ->assertSet('data.cards.limit', 50)
        ->assertSet('data.cards.order_by', 'name')
        ->assertSet('data.cards.order_direction', 'asc');
});

it('edits structured fields declared by a content type', function () {
    $user = User::factory()->create();
    $contentType = ContentType::factory()->create([
        'name' => 'Itinerary',
        'key' => 'itinerary',
        'schema' => ['fields' => [
            ['key' => 'summary', 'type' => 'textarea', 'label' => 'Summary', 'translatable' => true],
            ['key' => 'duration', 'type' => 'number', 'label' => 'Duration'],
            ['key' => 'cover_image', 'type' => 'image', 'label' => 'Cover image'],
        ]],
    ]);
    $content = Content::factory()->create([
        'content_type_id' => $contentType->id,
        'meta' => ['summary' => ['en' => 'Original summary'], 'duration' => 5],
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSee('Itinerary fields')
        ->assertSee('Original summary')
        ->assertSee('Cover image')
        ->call('updateContentTypeField', 'summary', 'Updated summary', true)
        ->call('updateContentTypeField', 'duration', 7, false)
        ->assertHasNoErrors();

    expect($content->fresh()->meta)
        ->toMatchArray([
            'summary' => ['en' => 'Updated summary'],
            'duration' => 7,
        ]);
});

it('does not delete content when its space is deleted', function () {
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
    ]);

    expect(fn () => $space->delete())->toThrow(QueryException::class)
        ->and(Content::whereKey($content->id)->exists())->toBeTrue();
});

it('prevents deleting a space that still has content', function () {
    $space = Space::factory()->create();
    Content::factory()->create([
        'space_id' => $space->id,
    ]);

    Livewire::test(SpacesIndex::class)
        ->call('deleteSpace', $space->id)
        ->assertHasErrors(['space'])
        ->assertDispatched('error');

    expect(Space::whereKey($space->id)->exists())->toBeTrue();
});
