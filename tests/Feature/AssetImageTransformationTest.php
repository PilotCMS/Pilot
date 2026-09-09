<?php

use Illuminate\Support\Facades\Storage;
use Pilot\Core\Models\Asset;
use Pilot\Core\Support\Cms\AssetImageTransformation;
use Pilot\Core\Support\Cms\AssetImageTransformer;

function pilotTestImage(int $width = 800, int $height = 400): string
{
    $image = imagecreatetruecolor($width, $height);
    $red = imagecolorallocate($image, 240, 20, 20);
    $blue = imagecolorallocate($image, 20, 20, 240);
    imagefilledrectangle($image, 0, 0, (int) ($width / 2) - 1, $height, $red);
    imagefilledrectangle($image, (int) ($width / 2), 0, $width, $height, $blue);
    ob_start();
    imagejpeg($image, null, 95);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents;
}

it('generates a versioned just in time image url', function () {
    $asset = Asset::factory()->create([
        'filename' => 'wide photo.jpg',
        'path' => 'assets/wide-photo.jpg',
        'mime' => 'image/jpeg',
        'checksum' => 'source-checksum',
        'focal_x' => 75,
        'focal_y' => 50,
    ]);

    $url = $asset->imageUrl(400, 400);

    expect($url)
        ->toContain("/assets/{$asset->id}/wide%20photo.jpg")
        ->toContain('size=400x400')
        ->toContain('v='.$asset->imageCacheVersion());
});

it('exposes a transformable delivery url and serves the original without a size', function () {
    Storage::fake('public');
    $contents = pilotTestImage();
    Storage::disk('public')->put('assets/photo.jpg', $contents);
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'photo.jpg',
        'path' => 'assets/photo.jpg',
        'mime' => 'image/jpeg',
        'checksum' => hash('sha256', $contents),
    ]);

    expect($asset->deliveryUrl())->toBe("/assets/{$asset->id}/photo.jpg");

    $response = $this->get($asset->deliveryUrl());
    $response->assertRedirectContains('v='.$asset->imageCacheVersion());

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('crops around the focal point and caches the transformed image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('assets/wide.jpg', pilotTestImage());

    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'wide.jpg',
        'path' => 'assets/wide.jpg',
        'mime' => 'image/jpeg',
        'width' => 800,
        'height' => 400,
        'checksum' => hash('sha256', pilotTestImage()),
        'focal_x' => 100,
        'focal_y' => 50,
    ]);

    $response = $this->withHeader('Accept', 'image/webp')->get($asset->imageUrl(400, 400));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('Vary', 'Accept');

    $variants = Storage::disk('public')->allFiles("assets/transforms/{$asset->id}");
    expect($variants)->toHaveCount(1);

    $image = imagecreatefromstring(Storage::disk('public')->get($variants[0]));
    expect(imagesx($image))->toBe(400)
        ->and(imagesy($image))->toBe(400);

    $center = imagecolorsforindex($image, imagecolorat($image, 200, 200));
    expect($center['blue'])->toBeGreaterThan($center['red']);
    imagedestroy($image);

    $this->withHeader('Accept', 'image/webp')->get($asset->imageUrl(400, 400))->assertOk();
    expect(Storage::disk('public')->allFiles("assets/transforms/{$asset->id}"))->toHaveCount(1);
});

it('redirects unversioned image requests to a cache safe url', function () {
    Storage::fake('public');
    Storage::disk('public')->put('assets/photo.jpg', pilotTestImage());
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'photo.jpg',
        'path' => 'assets/photo.jpg',
        'mime' => 'image/jpeg',
    ]);

    $this->get("/assets/{$asset->id}/photo.jpg?size=200x200")
        ->assertRedirectContains('size=200x200')
        ->assertRedirectContains('v='.$asset->imageCacheVersion());
});

it('rejects transformations outside the configured bounds', function () {
    Storage::fake('public');
    Storage::disk('public')->put('assets/photo.jpg', pilotTestImage());
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'photo.jpg',
        'path' => 'assets/photo.jpg',
        'mime' => 'image/jpeg',
    ]);

    $this->get("/assets/{$asset->id}/photo.jpg?size=5000x5000&v={$asset->imageCacheVersion()}")
        ->assertSessionHasErrors(['width', 'height', 'size']);
});

it('falls back to the image center when no focal point is stored', function () {
    Storage::fake('public');
    Storage::disk('public')->put('assets/photo.jpg', pilotTestImage());
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'photo.jpg',
        'path' => 'assets/photo.jpg',
        'mime' => 'image/jpeg',
        'focal_x' => null,
        'focal_y' => null,
    ]);
    $transformation = new AssetImageTransformation(400, 400, 'cover', 'webp', 80);

    $variant = app(AssetImageTransformer::class)->transform($asset, $transformation);
    $image = imagecreatefromstring(Storage::disk('public')->get($variant->path));
    $left = imagecolorsforindex($image, imagecolorat($image, 50, 200));
    $right = imagecolorsforindex($image, imagecolorat($image, 350, 200));

    expect($left['red'])->toBeGreaterThan($left['blue'])
        ->and($right['blue'])->toBeGreaterThan($right['red']);
    imagedestroy($image);
});

it('clears stale variants when the focal point changes or the asset is deleted', function () {
    Storage::fake('public');
    Storage::disk('public')->put('assets/photo.jpg', pilotTestImage());
    $asset = Asset::factory()->create([
        'disk' => 'public',
        'filename' => 'photo.jpg',
        'path' => 'assets/photo.jpg',
        'mime' => 'image/jpeg',
        'checksum' => 'original',
    ]);
    $transformation = new AssetImageTransformation(400, 400, 'cover', 'webp', 80);

    app(AssetImageTransformer::class)->transform($asset, $transformation);
    expect(Storage::disk('public')->directoryExists("assets/transforms/{$asset->id}"))->toBeTrue();

    $asset->update(['focal_x' => 25]);
    expect(Storage::disk('public')->directoryExists("assets/transforms/{$asset->id}"))->toBeFalse();

    app(AssetImageTransformer::class)->transform($asset, $transformation);
    $asset->delete();
    expect(Storage::disk('public')->directoryExists("assets/transforms/{$asset->id}"))->toBeFalse();
});
