<?php

use App\Livewire\Admin\Content\Editor;
use App\Livewire\Admin\Spaces\Edit;
use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\BlockType;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\Space;
use Pilot\Core\Models\SpacePreviewTarget;
use Pilot\Core\Support\Cms\ContentLifecycle;

it('saves named preview targets from space settings', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();

    Livewire::actingAs($user)
        ->test(Edit::class, ['space' => $space])
        ->set('previewTargets', [
            [
                'id' => null,
                'name' => 'Production',
                'url' => 'https://mysite.com',
                'is_default' => false,
            ],
            [
                'id' => null,
                'name' => 'Local',
                'url' => 'https://mysite.test',
                'is_default' => true,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($space->previewTargets()->count())->toBe(2)
        ->and($space->previewTargets()->where('is_default', true)->first()?->name)->toBe('Local');
});

it('generates and displays the frontend preview secret in space settings', function () {
    config(['pilot.preview.secret' => null]);

    $user = User::factory()->create();
    $space = Space::factory()->create();

    Livewire::actingAs($user)
        ->test(Edit::class, ['space' => $space])
        ->assertSee('PILOT_PREVIEW_SECRET=');

    $secret = CmsSetting::get('preview_secret');

    expect($secret)->toStartWith('pilot_')
        ->and(config('pilot.preview.secret'))->toBe($secret);
});

it('shows preview targets in the content editor and generates external preview urls', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'home',
        'name' => 'Home',
        'created_by' => $user->id,
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'name' => 'Local',
        'url' => 'https://mysite.test',
        'is_default' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSee('Local')
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->assertSee('https://mysite.test/_pilot/preview/'.$content->id, false)
        ->assertSee('https://mysite.test', false)
        ->assertSee('pilot_in_context_panel=0', false)
        ->assertSee('pilot-in-context-field-updated', false);
});

it('keeps the selected preview target after undoing a change', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'home',
        'name' => 'Home',
        'created_by' => $user->id,
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'name' => 'Production',
        'url' => 'https://production.test',
        'is_default' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->call('updateContent', 'name', 'Landing')
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->call('undoLastChange')
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->assertSee('https://production.test/_pilot/preview/'.$content->id, false);
});

it('keeps the selected preview target when inspecting a revision', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'home',
        'name' => 'Home',
        'created_by' => $user->id,
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'name' => 'Production',
        'url' => 'https://production.test',
        'is_default' => true,
    ]);

    $revision = app(ContentLifecycle::class)->createRevision($content, 'Production checkpoint', $user->id);

    Livewire::actingAs($user)
        ->test(Editor::class, ['content' => $content])
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->call('selectRevision', $revision->id)
        ->assertSet('selectedRevisionId', $revision->id)
        ->assertSet('selectedPreviewTargetId', $target->id)
        ->assertSee('https://production.test/_pilot/preview/'.$content->id, false)
        ->assertDontSee('revision='.$revision->id, false);
});

it('serves and updates in-context block fields from the package preview routes', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();

    BlockType::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'schema' => [
            'fields' => [
                [
                    'type' => 'text',
                    'key' => 'title',
                    'label' => 'Title',
                    'translatable' => true,
                ],
                [
                    'type' => 'number',
                    'key' => 'priority',
                    'label' => 'Priority',
                ],
            ],
        ],
    ]);

    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'home',
        'name' => 'Home',
        'created_by' => $user->id,
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => [
            'title' => ['en' => 'Editable title'],
            'priority' => 1,
        ],
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'url' => url(''),
    ]);

    $query = parse_url($target->previewUrlFor($content), PHP_URL_QUERY);

    $this->getJson('/_pilot/in-context/blocks/'.$block->id.'?'.$query)
        ->assertOk()
        ->assertJsonPath('block.id', $block->id)
        ->assertJsonPath('block.name', 'Hero')
        ->assertJsonPath('block.content.id', $content->id)
        ->assertJsonStructure(['block' => ['updatedAt', 'content' => ['updatedAt', 'syncKey']]])
        ->assertJsonPath('block.data.title', 'Editable title')
        ->assertJsonPath('block.schema.fields.0.key', 'title');

    $this->getJson('/_pilot/in-context/contents/'.$content->id.'/sync?'.$query)
        ->assertOk()
        ->assertJsonPath('content.id', $content->id)
        ->assertJsonStructure(['content' => ['updatedAt', 'syncKey'], 'serverTime']);

    $this->patchJson('/_pilot/in-context/blocks/'.$block->id.'?'.$query, [
        'fields' => [
            'title' => 'Updated from package panel',
            'priority' => '7',
            'ignored' => 'Not in schema',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('updated', true)
        ->assertJsonPath('block.data.title', 'Updated from package panel')
        ->assertJsonPath('block.data.priority', 7)
        ->assertJsonStructure(['block' => ['updatedAt', 'content' => ['updatedAt', 'syncKey']]]);

    $block->refresh();

    expect($block->data['title']['en'])->toBe('Updated from package panel')
        ->and($block->data['priority'])->toBe(7)
        ->and($block->data)->not->toHaveKey('ignored');
});

it('updates structured json object list fields from the package preview routes', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();

    BlockType::factory()->create([
        'key' => 'itinerary',
        'name' => 'Itinerary',
        'schema' => [
            'fields' => [
                [
                    'type' => 'repeater',
                    'key' => 'days',
                    'label' => 'Days',
                ],
            ],
        ],
    ]);

    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'road-trip',
        'name' => 'Road Trip',
        'created_by' => $user->id,
    ]);

    $block = Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'itinerary',
        'data' => [
            'days' => [
                [
                    'day' => '1',
                    'body' => 'Start at the north gate.',
                    'meta' => '90 mi',
                    'title' => 'Gardiner',
                ],
            ],
        ],
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'url' => url(''),
    ]);

    $query = parse_url($target->previewUrlFor($content), PHP_URL_QUERY);

    $this->patchJson('/_pilot/in-context/blocks/'.$block->id.'?'.$query, [
        'fields' => [
            'days' => [
                [
                    'day' => '1',
                    'body' => 'Start at the north gate, then follow the Yellowstone River.',
                    'meta' => '92 mi',
                    'title' => 'Gardiner and Paradise Valley',
                ],
                [
                    'day' => '2',
                    'body' => 'Add a museum morning and dinner downtown.',
                    'meta' => '1 night',
                    'title' => 'Bozeman',
                ],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('block.data.days.0.title', 'Gardiner and Paradise Valley')
        ->assertJsonPath('block.data.days.1.meta', '1 night');

    $block->refresh();

    expect($block->data['days'])->toHaveCount(2)
        ->and($block->data['days'][0]['body'])->toBe('Start at the north gate, then follow the Yellowstone River.')
        ->and($block->data['days'][1]['title'])->toBe('Bozeman');
});

it('renders draft content through a valid package preview url only', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'draft-home',
        'name' => 'Draft Home',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Draft preview from frontend package'],
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'url' => url(''),
    ]);

    $this->get('/_pilot/preview/'.$content->id)
        ->assertForbidden();

    $this->get($target->previewUrlFor($content))
        ->assertOk()
        ->assertSee('Draft preview from frontend package')
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee('data-pilot-editable="field"', false)
        ->assertSee('pilot-preview-toolbar', false)
        ->assertSee('pilot-preview-block-action', false)
        ->assertSee('window.__pilotInContextLoaded', false)
        ->assertSee('pilot-in-context-panel-root', false);
});

it('hides only the package in-context panel when preview is embedded in the cms editor', function () {
    $user = User::factory()->create();
    $space = Space::factory()->create();
    $content = Content::factory()->create([
        'space_id' => $space->id,
        'slug' => 'draft-home',
        'name' => 'Draft Home',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => ['title' => 'Draft preview from frontend package'],
    ]);

    $target = SpacePreviewTarget::factory()->create([
        'space_id' => $space->id,
        'url' => url(''),
    ]);

    $this->get($target->previewUrlFor($content).'&pilot_in_context_panel=0')
        ->assertOk()
        ->assertSee('Draft preview from frontend package')
        ->assertSee('data-pilot-editable="block"', false)
        ->assertSee('data-pilot-editable="field"', false)
        ->assertSee('disablePreviewLinkNavigation', false)
        ->assertSee('pilot-preview-select-block', false)
        ->assertSee('pilot-preview-toolbar', false)
        ->assertSee('pilot-preview-block-action', false)
        ->assertSee('.pilot-preview-toolbar [data-pilot-action]', false)
        ->assertDontSee("scrollIntoView({ behavior: 'smooth', block: 'center' })", false)
        ->assertDontSee('pilot-preview-navigated', false)
        ->assertDontSee('preserveEditorPreviewMode', false)
        ->assertSee('window.__pilotInContextLoaded', false)
        ->assertSee("CMS_PREVIEW_FRAME_NAME = 'pilot-cms-preview'", false)
        ->assertSee('window.name === CMS_PREVIEW_FRAME_NAME', false)
        ->assertSee('let panelEnabled = (() => {', false)
        ->assertSee("get('pilot_in_context_panel')", false)
        ->assertSee('disablePanel', false)
        ->assertSee('pilot-preview-editor-mode', false)
        ->assertSee('if (panelEnabled) {', false)
        ->assertSee('buildPanel();', false);
});
