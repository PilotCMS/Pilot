<?php

use App\Models\Asset;

it('keeps a relative url unchanged', function () {
    expect(Asset::toRelativeUrl('/storage/assets/example.png'))
        ->toBe('/storage/assets/example.png');
});

it('converts an absolute url to relative path including query string', function () {
    expect(Asset::toRelativeUrl('http://localhost:8000/storage/assets/example.png?x=1'))
        ->toBe('/storage/assets/example.png?x=1');
});

it('keeps an externally hosted asset url absolute', function () {
    expect(Asset::toRelativeUrl('https://images.example.com/photo.jpg?width=1200#hero'))
        ->toBe('https://images.example.com/photo.jpg?width=1200#hero');
});
