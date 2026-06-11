<?php

use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;

it('renders published home content with Laravel block views', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
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
        'content_id' => $home->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [
            'title' => 'Welcome to Pilot',
            'subtitle' => 'This is the public Laravel view',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome to Pilot')
        ->assertSee('This is the public Laravel view')
        ->assertSee('meta name="pilot-content-id" content="'.$home->id.'"', false)
        ->assertSee('data-pilot-content-id="'.$home->id.'"', false)
        ->assertDontSee('pilot-preview-navigated', false);
});

it('does not render draft pages on the public route', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'pricing',
        'name' => 'Pricing',
        'status' => 'draft',
        'published_at' => null,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->get('/pricing')->assertNotFound();
});

it('renders fallback component when a Laravel block view is missing', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
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
        'content_id' => $home->id,
        'type' => 'feature_grid',
        'position' => 0,
        'data' => [
            'headline' => 'Fallback field value',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Fallback preview')
        ->assertSee('feature_grid')
        ->assertSee('Fallback field value');
});

it('renders a cta block with a root Laravel component', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
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
        'content_id' => $home->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => 'Start building with Pilot',
            'button_text' => 'Open editor',
            'button_url' => '/admin/content',
            'style' => 'primary',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Start building with Pilot')
        ->assertSee('Open editor')
        ->assertSee('href="/admin/content"', false)
        ->assertDontSee('Fallback preview');
});

it('renders nested blocks inside a columns block', function () {
    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $columns = Block::create([
        'content_id' => $home->id,
        'type' => 'columns',
        'position' => 0,
        'data' => [
            'columns' => 2,
        ],
    ]);

    Block::create([
        'content_id' => $home->id,
        'parent_block_id' => $columns->id,
        'type' => 'cta',
        'position' => 0,
        'data' => [
            'title' => 'Nested CTA',
            'button_text' => 'Read more',
            'button_url' => '/nested',
            'style' => 'secondary',
            '_column' => 0,
        ],
    ]);

    Block::create([
        'content_id' => $home->id,
        'parent_block_id' => $columns->id,
        'type' => 'richtext',
        'position' => 1,
        'data' => [
            'content' => '<p>Nested rich text</p>',
            '_column' => 1,
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Nested CTA')
        ->assertSee('Read more')
        ->assertSee('Nested rich text', false)
        ->assertSee('href="/nested"', false);
});

it('prefixes relative asset paths with the configured Pilot asset URL', function () {
    config(['pilot.assets.base_url' => 'https://cms.test']);

    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
        'space_id' => $space->id,
        'type' => 'page',
        'slug' => 'home',
        'name' => 'Home',
        'status' => 'published',
        'published_at' => now(),
        'meta' => [
            'og_image' => '/storage/assets/social-card.jpg',
        ],
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $home->id,
        'type' => 'image',
        'position' => 0,
        'data' => [
            'image' => '/storage/assets/hero.jpg',
            'image_focal_x' => 24,
            'image_focal_y' => 76,
            'alt' => 'Hero image',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('content="https://cms.test/storage/assets/social-card.jpg"', false)
        ->assertSee('src="https://cms.test/storage/assets/hero.jpg"', false)
        ->assertSee('object-position: 24% 76%;', false);
});

it('does not prefix absolute asset urls', function () {
    config(['pilot.assets.base_url' => 'https://cms.test']);

    $user = User::factory()->create();
    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $home = Content::create([
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
        'content_id' => $home->id,
        'type' => 'image',
        'position' => 0,
        'data' => [
            'image' => 'https://cdn.test/hero.jpg',
            'alt' => 'Hero image',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('src="https://cdn.test/hero.jpg"', false)
        ->assertDontSee('https://cms.test/https://cdn.test/hero.jpg', false);
});
