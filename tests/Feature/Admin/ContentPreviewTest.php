<?php

use App\Models\User;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;

it('directs editors to configure a frontend when no preview target exists', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Marketing', 'slug' => 'marketing']);
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
        ->assertSee('Connect a frontend preview')
        ->assertSee('pilot/laravel')
        ->assertDontSee('name="pilot-cms-preview"', false)
        ->assertDontSee('aria-label="Open preview"', false);
});

it('renders compose blocks as semantic summaries without implementation json', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Marketing', 'slug' => 'marketing']);
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
        ->assertSee('Preview CTA')
        ->assertDontSee('Fallback preview')
        ->assertDontSee('"en": "Preview CTA"')
        ->assertDontSee('"en": "/signup"');
});

it('renders the content editor when image preview fields are localized arrays', function () {
    $user = User::factory()->create();
    $space = Space::create(['name' => 'Marketing', 'slug' => 'marketing']);
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
            'alt' => ['en' => 'About us image', 'fr' => 'Image a propos de nous'],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.content.editor', $content))
        ->assertOk()
        ->assertSee('About us image')
        ->assertDontSee('htmlspecialchars(): Argument #1', false);
});
