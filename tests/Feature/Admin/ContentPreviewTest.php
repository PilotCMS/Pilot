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
        ->assertSee('Fallback text');
});
