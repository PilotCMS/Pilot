<?php

use App\Models\Block;
use App\Models\BlockType;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;

it('gates the front-end editor script behind authentication', function () {
    $this->get(route('cms.frontend-editor.script'))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('cms.frontend-editor.script'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertSee('Pilot editor');
});

it('only injects the front-end editor on public pages for logged in users', function () {
    [$content] = createFrontendEditorContent();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee(route('cms.frontend-editor.script'), false)
        ->assertDontSee('<div data-pilot-editable', false);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('cms.frontend-editor.script'), false)
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee($content->name);
});

it('returns schema data for a selected block', function () {
    [, $block] = createFrontendEditorContent();

    $this->actingAs(User::factory()->create())
        ->getJson(route('cms.frontend-editor.blocks.show', $block))
        ->assertOk()
        ->assertJsonPath('block.id', $block->id)
        ->assertJsonPath('block.type', 'hero')
        ->assertJsonPath('block.data.title', 'Welcome')
        ->assertJsonPath('block.schema.fields.0.key', 'title');
});

it('updates editable block fields in context', function () {
    [, $block] = createFrontendEditorContent();
    $editor = User::factory()->create();

    $this->actingAs($editor)
        ->patchJson(route('cms.frontend-editor.blocks.update', $block), [
            'locale' => 'en',
            'fields' => [
                'title' => 'Updated in place',
                'subtitle' => 'Saved from the front-end editor',
                'ignored' => 'This field is not in the schema',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('updated', true)
        ->assertJsonPath('block.data.title', 'Updated in place');

    $block->refresh();

    expect($block->data['title']['en'])->toBe('Updated in place')
        ->and($block->data['subtitle']['en'])->toBe('Saved from the front-end editor')
        ->and($block->data)->not->toHaveKey('ignored')
        ->and($block->content->fresh()->updated_by)->toBe($editor->id);
});

function createFrontendEditorContent(): array
{
    $user = User::factory()->create();

    BlockType::create([
        'key' => 'hero',
        'name' => 'Hero',
        'icon' => 'photo',
        'is_global' => false,
        'schema' => [
            'fields' => [
                [
                    'type' => 'text',
                    'key' => 'title',
                    'label' => 'Title',
                    'translatable' => true,
                ],
                [
                    'type' => 'textarea',
                    'key' => 'subtitle',
                    'label' => 'Subtitle',
                    'translatable' => true,
                ],
            ],
        ],
    ]);

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

    $block = Block::create([
        'content_id' => $content->id,
        'type' => 'hero',
        'position' => 0,
        'data' => [
            'title' => ['en' => 'Welcome'],
            'subtitle' => ['en' => 'Original subtitle'],
        ],
    ]);

    return [$content, $block];
}
