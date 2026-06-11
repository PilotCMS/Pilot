<?php

use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('renders posted headless content for live preview', function () {
    $this->postJson(route('api.preview.render'), [
        'source' => 'headless',
        'content' => [
            'slug' => 'preview-home',
            'name' => 'Preview Home',
            'body' => [
                [
                    '_uid' => 'draft-hero',
                    'component' => 'hero',
                    'data' => [
                        'title' => 'Typed in the editor',
                        'subtitle' => 'Rendered without a database row',
                    ],
                ],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('source', 'headless')
        ->assertJsonPath('content.slug', 'preview-home')
        ->assertSee('Typed in the editor')
        ->assertSee('Rendered without a database row');
});

it('renders mysql content for live preview when no headless payload is posted', function () {
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
        'status' => 'draft',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => ['title' => 'Draft database preview'],
    ]);

    $previewUrl = URL::temporarySignedRoute('api.preview.render', now()->addMinutes(15), [
        'content_id' => $content->id,
    ]);

    $this->postJson($previewUrl, [
        'source' => 'mysql',
    ])
        ->assertOk()
        ->assertJsonPath('source', 'mysql')
        ->assertJsonPath('content.slug', 'home')
        ->assertSee('Draft database preview');
});
