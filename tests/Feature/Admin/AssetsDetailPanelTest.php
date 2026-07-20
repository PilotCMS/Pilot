<?php

use App\Livewire\Admin\Assets\AssetPickerModal;
use App\Livewire\Admin\Assets\Index;
use App\Models\Asset;
use App\Models\Block;
use App\Models\Content;
use App\Models\Space;
use App\Models\User;
use App\Support\Cms\AssetUsageFinder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders asset detail panel with copy link control without blade errors', function () {
    $user = User::factory()->create();

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $asset = Asset::create([
        'space_id' => $space->id,
        'folder_id' => null,
        'disk' => 'public',
        'path' => 'assets/example.png',
        'filename' => 'example.png',
        'display_name' => 'Example Image',
        'mime' => 'image/png',
        'size' => 1024,
        'width' => null,
        'height' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openAssetDetail', $asset->id)
        ->assertSet('showDetailSlideOver', true)
        ->assertSee('Asset URL')
        ->assertSee($asset->relativeUrl())
        ->assertSee('x-bind:title');
});

it('uses relative asset urls in picker thumbnails', function () {
    $user = User::factory()->create();

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $asset = Asset::create([
        'space_id' => $space->id,
        'folder_id' => null,
        'disk' => 'public',
        'path' => 'assets/example.png',
        'filename' => 'example.png',
        'display_name' => 'Example Image',
        'mime' => 'image/png',
        'size' => 1024,
        'width' => null,
        'height' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(AssetPickerModal::class)
        ->call('open', 'image')
        ->assertSee($asset->relativeUrl());
});

it('renders external stock assets without requiring a configured filesystem disk', function () {
    $user = User::factory()->create();

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $asset = Asset::create([
        'space_id' => $space->id,
        'folder_id' => null,
        'disk' => 'stock',
        'path' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=2400&q=85',
        'filename' => 'glacier-lake.jpg',
        'display_name' => 'Glacier Lake',
        'mime' => 'image/jpeg',
        'size' => 1024,
        'width' => null,
        'height' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee($asset->url())
        ->call('openAssetDetail', $asset->id)
        ->assertSee($asset->url());
});

it('persists focal point coordinates when saving asset details', function () {
    $user = User::factory()->create();

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $asset = Asset::create([
        'space_id' => $space->id,
        'folder_id' => null,
        'disk' => 'public',
        'path' => 'assets/example.png',
        'filename' => 'example.png',
        'display_name' => 'Example Image',
        'mime' => 'image/png',
        'size' => 1024,
        'width' => null,
        'height' => null,
        'focal_x' => null,
        'focal_y' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openAssetDetail', $asset->id)
        ->call('setFocalPoint', 22.4, 78.6)
        ->call('saveAssetDetails');

    $asset->refresh();

    expect($asset->focal_x)->toBe(22.4);
    expect($asset->focal_y)->toBe(78.6);
});

it('extracts technical metadata when uploading assets', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $space = Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $file = UploadedFile::fake()->image('hero.jpg', 640, 360);
    $expectedChecksum = hash_file('sha256', $file->getRealPath());

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('spaceId', $space->id)
        ->set('uploadFiles', [$file])
        ->call('uploadAssets');

    $asset = Asset::query()->where('filename', 'hero.jpg')->firstOrFail();

    expect($asset->width)->toBe(640)
        ->and($asset->height)->toBe(360)
        ->and($asset->checksum)->toBe($expectedChecksum)
        ->and($asset->metadata['client_original_name'])->toBe('hero.jpg');

    Storage::disk('public')->assertExists($asset->path);
});

it('scopes upload modal loading state to file and submit requests', function () {
    $user = User::factory()->create();

    Space::create([
        'name' => 'Website',
        'slug' => 'website',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('showUploadModal', true)
        ->assertSeeHtml('wire:loading.attr="disabled" wire:target="uploadFiles,uploadAssets"')
        ->assertSeeHtml('wire:loading.remove wire:target="uploadFiles,uploadAssets"')
        ->assertSeeHtml('wire:loading wire:target="uploadFiles,uploadAssets"');
});

it('persists governance metadata from the asset detail panel', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'filename' => 'campaign.jpg',
        'display_name' => 'Campaign',
        'mime' => 'image/jpeg',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openAssetDetail', $asset->id)
        ->set('editDescription', 'Hero image for spring campaign.')
        ->set('editAlt', 'A person holding the spring catalog.')
        ->set('editTitle', 'Spring Catalog')
        ->set('editCredit', 'Jane Smith')
        ->set('editCopyright', 'Pilot Inc.')
        ->set('editLicense', 'Owned')
        ->set('editSourceUrl', 'https://example.com/source')
        ->set('editExpiresAt', '2026-12-31')
        ->call('saveAssetDetails');

    $asset->refresh();

    expect($asset->description)->toBe('Hero image for spring campaign.')
        ->and($asset->alt)->toBe('A person holding the spring catalog.')
        ->and($asset->title)->toBe('Spring Catalog')
        ->and($asset->credit)->toBe('Jane Smith')
        ->and($asset->copyright)->toBe('Pilot Inc.')
        ->and($asset->license)->toBe('Owned')
        ->and($asset->source_url)->toBe('https://example.com/source')
        ->and($asset->expires_at?->toDateString())->toBe('2026-12-31');
});

it('finds content usage for asset urls stored in block data', function () {
    $asset = Asset::factory()->create([
        'path' => 'assets/hero.jpg',
        'filename' => 'hero.jpg',
        'mime' => 'image/jpeg',
    ]);

    $content = Content::factory()->create([
        'space_id' => $asset->space_id,
        'name' => 'Home',
    ]);

    Block::factory()->create([
        'content_id' => $content->id,
        'type' => 'hero',
        'data' => [
            'image' => $asset->relativeUrl(),
            'nested' => [
                'caption' => 'Hero',
            ],
        ],
    ]);

    $references = app(AssetUsageFinder::class)->forAsset($asset);

    expect($references)->toHaveCount(1)
        ->and($references->first()['content']->is($content))->toBeTrue()
        ->and($references->first()['location'])->toContain('image');
});

it('blocks deletion when an asset is still used by content', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'path' => 'assets/hero.jpg',
        'filename' => 'hero.jpg',
        'mime' => 'image/jpeg',
    ]);

    Storage::disk('public')->put($asset->path, 'image');

    $content = Content::factory()->create(['space_id' => $asset->space_id]);
    Block::factory()->create([
        'content_id' => $content->id,
        'data' => ['image' => $asset->relativeUrl()],
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('deleteAsset', $asset->id)
        ->assertHasErrors('deleteAsset');

    expect(Asset::query()->whereKey($asset->id)->exists())->toBeTrue();
    Storage::disk('public')->assertExists($asset->path);
});
