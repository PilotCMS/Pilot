<?php

use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;

it('returns storyblok style content with editor links and localized block data', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'published',
        'published_at' => now(),
        'meta' => ['meta_title' => 'Pilot Home'],
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [
            'title' => [
                'en' => 'Welcome',
                'fr' => 'Bienvenue',
            ],
        ],
    ]);

    $this->getJson('/api/v1/spaces/website/contents/home?locale=fr')
        ->assertOk()
        ->assertJsonPath('story.slug', 'home')
        ->assertJsonPath('story.content.component', 'page')
        ->assertJsonPath('story.content.body.0._uid', $block->id)
        ->assertJsonPath('story.content.body.0.component', 'hero')
        ->assertJsonPath('story.content.body.0.data.title', 'Bienvenue')
        ->assertJsonPath('story.links.editor', url('/admin/content/'.$content->id.'/edit'));
});

it('keeps editor attributes out of normal public rendering', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $content = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => ['title' => 'Public Home'],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Public Home')
        ->assertDontSee('<div data-pilot-editable', false);

    $this->get(route('home', ['pilot_editor' => 1]))
        ->assertOk()
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee('disablePreviewLinkNavigation', false)
        ->assertSee('event.preventDefault();', false)
        ->assertSee('pilot-preview-select-block', false)
        ->assertDontSee('pilot-preview-navigated', false)
        ->assertDontSee("url.searchParams.set('pilot_editor', '1')", false)
        ->assertDontSee("url.searchParams.set('pilot_in_context', '0')", false);
});
