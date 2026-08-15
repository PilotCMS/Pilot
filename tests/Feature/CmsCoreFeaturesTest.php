<?php

use App\Livewire\Admin\Content\BlockEditor;
use App\Livewire\Admin\Content\Editor;
use App\Livewire\Admin\Spaces\Index as SpacesIndex;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentReference;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Redirect;
use Pilot\Core\Models\Space;
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

it('creates redirects when content slugs change and resolves them publicly', function () {
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

    $this->get('/old-page')
        ->assertRedirect('/new-page');
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

it('renders canonical robots and open graph seo metadata', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create(['slug' => 'website']);

    Content::factory()->published()->create([
        'space_id' => $space->id,
        'slug' => 'seo-page',
        'name' => 'SEO Page',
        'created_by' => $user->id,
        'meta' => [
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description',
            'canonical_url' => 'https://example.com/seo-page',
            'og_image' => 'https://example.com/og.jpg',
            'noindex' => true,
        ],
    ]);

    $this->get('/seo-page')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://example.com/seo-page">', false)
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
        ->assertSee('<meta property="og:title" content="Custom SEO Title">', false)
        ->assertSee('<meta property="og:image" content="https://example.com/og.jpg">', false);
});
