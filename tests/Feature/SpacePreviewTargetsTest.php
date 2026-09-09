<?php

use App\Models\User;
use Livewire\Livewire;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Livewire\Admin\Spaces\Edit;
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
            ['id' => null, 'name' => 'Production', 'url' => 'https://mysite.com', 'is_default' => false],
            ['id' => null, 'name' => 'Local', 'url' => 'https://mysite.test', 'is_default' => true],
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
        ->assertSee('pilot_in_context=1', false)
        ->assertSee('x-ref="previewFramePrimary"', false)
        ->assertSee('x-ref="previewFrameSecondary"', false)
        ->assertSee('handlePreviewFrameLoad(1)', false)
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
