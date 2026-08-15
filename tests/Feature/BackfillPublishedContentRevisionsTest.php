<?php

use App\Models\User;
use Pilot\Core\Models\Block;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentRevision;

it('backfills published revisions for published content missing a published revision', function () {
    $user = User::factory()->create();
    $content = Content::factory()->published()->create([
        'name' => 'Published without revision',
        'published_revision_id' => null,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'data' => ['title' => 'Published snapshot'],
    ]);

    $this->artisan('pilot:backfill-published-revisions --dry-run')
        ->expectsOutputToContain('1 published content entries need a published revision.')
        ->assertSuccessful();

    expect($content->refresh()->published_revision_id)->toBeNull();

    $this->artisan('pilot:backfill-published-revisions')
        ->expectsOutputToContain('Backfilled 1 published content revisions.')
        ->assertSuccessful();

    $revision = ContentRevision::query()->where('content_id', $content->id)->firstOrFail();

    expect($content->refresh()->published_revision_id)->toBe($revision->id)
        ->and($revision->revision_type)->toBe('published')
        ->and($revision->label)->toBe('Published backfill')
        ->and($revision->meta['backfilled'])->toBeTrue()
        ->and($revision->snapshot['content']['name'])->toBe('Published without revision');
});
