<?php

use App\Livewire\Admin\Content\BlockEditor;
use App\Livewire\Admin\Content\Editor;
use App\Livewire\Admin\Spaces\Index as SpacesIndex;
use App\Models\Block;
use App\Models\BlockType;
use App\Models\Content;
use App\Models\ContentReference;
use App\Models\ContentType;
use App\Models\Redirect;
use App\Models\Space;
use App\Models\User;
use App\Support\Cms\ContentLifecycle;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

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
