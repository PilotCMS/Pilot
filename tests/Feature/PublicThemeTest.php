<?php

use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Config;

it('renders published home content with the sample theme', function () {
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
            'subtitle' => 'This is the public theme',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome to Pilot')
        ->assertSee('This is the public theme');
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

it('renders fallback component when a theme block template is missing', function () {
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
        ->assertSee('Missing theme component')
        ->assertSee('feature_grid')
        ->assertSee('Fallback field value');
});

it('renders using the marketing public theme when configured', function () {
    Config::set('cms.theme', 'marketing');

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
            'title' => 'Marketing Theme Home',
        ],
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Public Theme')
        ->assertSee('Marketing Theme Home');
});
