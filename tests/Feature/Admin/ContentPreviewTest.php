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
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee('data-pilot-block-id=', false)
        ->assertSee(route('cms.frontend-editor.script'), false);
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
        ->assertSee('href="'.route('admin.content.preview', $content).'"', false)
        ->assertSee('target="_blank"', false);
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

    Block::create([
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

    Block::create([
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
        ->assertSee('href="/nested-preview"', false);
});
