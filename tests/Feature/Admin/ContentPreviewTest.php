<?php

use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;

it('redirects guests from admin content preview', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->get(route('admin.content.preview', $content))
        ->assertRedirect(route('login'));
});

it('renders preview with fallback component card when a blade view is missing', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'missing_component',
        'position' => 0,
        'data' => [
            'headline' => 'Fallback text',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.preview', $content))
        ->assertOk()
        ->assertSee('Fallback preview')
        ->assertSee('missing_component')
        ->assertSee('Fallback text')
        ->assertSee('meta name="pilot-content-id" content="'.$content->id.'"', false)
        ->assertSee('data-pilot-content-id="'.$content->id.'"', false)
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee('data-pilot-block-id=', false)
        ->assertSee('disablePreviewLinkNavigation', false)
        ->assertSee('pilot-preview-select-block', false)
        ->assertSee('pilot-preview-sync-selected-block', false)
        ->assertSee('data-pilot-selected', false)
        ->assertSee('pilot-preview-toolbar', false)
        ->assertSee('pilot-preview-block-action', false)
        ->assertDontSee('pilot-preview-navigated', false)
        ->assertDontSee('preserveEditorPreviewMode', false)
        ->assertDontSee("url.searchParams.set('pilot_editor', '1')", false)
        ->assertDontSee("url.searchParams.set('pilot_in_context', '0')", false)
        ->assertSee('window.parent.postMessage', false)
        ->assertSee('window.__pilotInContextLoaded', false)
        ->assertSee('pilot-in-context-panel-root', false);
});

it('shows a preview link in the content editor toolbar', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.editor', $content))
        ->assertOk()
        ->assertSee('View preview')
        ->assertSee("canvasMode: 'preview'", false)
        ->assertSee('x-show="canvasMode === \'compose\'" class="flex min-h-0 flex-1 overflow-hidden"', false)
        ->assertSee('class="h-full min-h-0 max-w-full overflow-y-auto bg-white', false)
        ->assertSee('href="'.route('admin.content.preview', $content).'"', false)
        ->assertSee('pilot_in_context_panel=0', false)
        ->assertSee('x-ref="previewFrame"', false)
        ->assertSee('wire:ignore', false)
        ->assertSee('name="pilot-cms-preview"', false)
        ->assertSee('preview-frame-refresh', false)
        ->assertSee('pilot-preview-editor-mode', false)
        ->assertSee('inContextPanel: false', false)
        ->assertSee('syncPreviewSelection', false)
        ->assertDontSee('scrollSelectedBlockIntoView', false)
        ->assertSee('pilot-preview-sync-selected-block', false)
        ->assertSee('pilot-preview-block-action', false)
        ->assertDontSee('pilot-preview-navigated', false)
        ->assertDontSee('openContentFromPreview', false)
        ->assertSee('target="_blank"', false);
});

it('renders compose blocks as fallback previews with formatted json', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => ['en' => 'Preview CTA'],
            'button_text' => ['en' => 'Take action'],
            'button_url' => ['en' => '/signup'],
            'enabled' => true,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.editor', $content))
        ->assertOk()
        ->assertSee('Fallback preview')
        ->assertSee('"en": "Preview CTA"')
        ->assertSee('"en": "/signup"')
        ->assertSee('true');
});

it('renders the content editor when image preview fields are localized arrays', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'about-us',
        'name' => 'About Us',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'image',
        'position' => 0,
        'data' => [
            'image' => '/storage/about-us.jpg',
            'alt' => [
                'en' => 'About us image',
                'fr' => 'Image a propos de nous',
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.editor', $content))
        ->assertOk()
        ->assertSee('About us image')
        ->assertDontSee('htmlspecialchars(): Argument #1', false);
});

it('renders a cta block in the admin preview canvas', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => ['en' => 'Preview CTA'],
            'button_text' => ['en' => 'Take action'],
            'button_url' => ['en' => '/signup'],
            'style' => 'secondary',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.preview', $content))
        ->assertOk()
        ->assertSee('Preview CTA')
        ->assertSee('Take action')
        ->assertSee('href="/signup"', false)
        ->assertDontSee('Fallback preview');
});

it('renders nested columns content in the admin preview canvas', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Homepage',
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $columns = Block::create([
        'content_id' => $content->id,
        'type' => 'columns',
        'position' => 0,
        'data' => [
            'columns' => 2,
        ],
    ]);

    $nestedCta = Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => ['en' => 'Preview nested CTA'],
            'button_text' => ['en' => 'Nested action'],
            'button_url' => ['en' => '/nested-preview'],
            'style' => 'primary',
            '_column' => 0,
        ],
    ]);

    $nestedRichText = Block::create([
        'content_id' => $content->id,
        'parent_block_id' => $columns->id,
        'type' => 'richtext',
        'position' => 1,
        'data' => [
            'content' => ['en' => '<p>Preview nested rich text</p>'],
            '_column' => 1,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.preview', $content))
        ->assertOk()
        ->assertSee('Preview nested CTA')
        ->assertSee('Nested action')
        ->assertSee('Preview nested rich text', false)
        ->assertSee('href="/nested-preview"', false)
        ->assertSee('data-pilot-block-id="'.$columns->id.'"', false)
        ->assertSee('data-pilot-block-id="'.$nestedCta->id.'"', false)
        ->assertSee('data-pilot-block-id="'.$nestedRichText->id.'"', false);
});
