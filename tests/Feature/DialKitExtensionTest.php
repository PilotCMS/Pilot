<?php

test('dialkit extension manifest is present and valid', function () {
    $manifestPath = base_path('public/dialkit-extension/manifest.json');

    expect($manifestPath)->toBeFile();

    $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray([
        'manifest_version' => 3,
        'name' => 'Tweaker',
        'background' => ['service_worker' => 'background.js'],
    ]);

    expect($manifest['content_scripts'][0]['js'][0])->toBe('content.js');
    expect($manifest['content_scripts'][0]['css'][0])->toBe('styles.css');
});

test('dialkit extension background script is present', function () {
    $backgroundPath = base_path('public/dialkit-extension/background.js');

    expect($backgroundPath)->toBeFile();
    expect(file_get_contents($backgroundPath))->toContain('dialkit-toggle');
});

test('dialkit extension styles are isolated', function () {
    $stylesPath = base_path('public/dialkit-extension/styles.css');
    $contentPath = base_path('public/dialkit-extension/content.js');

    expect($stylesPath)->toBeFile();
    expect($contentPath)->toBeFile();

    expect(file_get_contents($stylesPath))->toContain('#dialkit-highlight');
    expect(file_get_contents($contentPath))->toContain('PANEL_CSS');
});
