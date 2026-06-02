<?php

use App\Livewire\Admin\Assets\AssetPickerModal;
use App\Livewire\Admin\Assets\Index;
use App\Models\Asset;
use App\Models\Space;
use App\Models\User;
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
