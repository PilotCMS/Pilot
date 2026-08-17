<?php

use Illuminate\Support\Facades\File;

test('admin button contracts include consistent size and interaction states', function () {
    $stylesheet = File::get(base_path('vendor/pilotcms/core/resources/css/app.css'));

    expect($stylesheet)
        ->toContain('.cms-btn:focus-visible')
        ->toContain('.cms-btn:disabled')
        ->toContain('.cms-iconbtn:focus-visible')
        ->toContain('.cms-iconbtn-danger:hover')
        ->toContain('background: var(--danger);')
        ->toContain('color: #fff !important;')
        ->toContain('.cms-seg-btn:focus-visible')
        ->toContain('.cms-seg-btn[aria-pressed="true"]')
        ->toContain('[data-flux-button].h-10')
        ->toContain('height: var(--control-h);')
        ->toContain('[data-flux-button].h-8')
        ->toContain('height: var(--control-h-sm);')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

test('raw blade buttons declare a safe button type', function () {
    $missingTypes = [];

    $viewsPath = base_path('vendor/pilotcms/core/resources/views');

    foreach (File::allFiles($viewsPath) as $file) {
        $relativePath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());

        if ($relativePath === 'components/jaunt/forms/icon-button.blade.php') {
            continue;
        }

        preg_match_all('/<button\b[^>]*>/s', $file->getContents(), $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$openingTag, $offset]) {
            if (preg_match('/\btype\s*=/', $openingTag)) {
                continue;
            }

            $line = substr_count(substr($file->getContents(), 0, $offset), "\n") + 1;
            $missingTypes[] = "{$relativePath}:{$line}";
        }
    }

    expect($missingTypes)->toBe([]);
});

test('segmented controls expose their selected state', function () {
    $contentIndex = File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/content/index.blade.php'));
    $assetIndex = File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/assets/index.blade.php'));
    $editor = File::get(base_path('vendor/pilotcms/core/resources/views/livewire/admin/content/editor.blade.php'));
    $javascript = File::get(base_path('vendor/pilotcms/core/resources/js/app.js'));

    expect($contentIndex)
        ->toContain('role="group" aria-label="Content type filter"')
        ->toContain('aria-pressed=');

    expect($assetIndex)
        ->toContain('role="group" aria-label="Sort assets"')
        ->toContain('role="tablist" aria-label="Asset details sections"')
        ->toContain('aria-controls="asset-panel-');

    expect($editor)
        ->toContain('role="group" aria-label="Editor view"')
        ->toContain('role="tablist" aria-label="Inspector sections"')
        ->toContain('aria-controls="inspector-panel-');

    expect($javascript)
        ->toContain("new Set(['ArrowLeft', 'ArrowRight', 'Home', 'End'])")
        ->toContain("closest?.('[data-cms-tabs] [role=\"tab\"]')")
        ->toContain('tabs[nextIndex].focus()')
        ->toContain('tabs[nextIndex].click()');
});
