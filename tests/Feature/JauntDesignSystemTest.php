<?php

use Illuminate\Support\Facades\File;

test('the latest Jaunt semantic color contract is installed', function () {
    $colors = File::get(base_path('vendor/pilotcms/core/resources/css/jaunt/tokens/colors.css'));

    expect($colors)
        ->toContain('--gray-900:  #1c1917;')
        ->toContain('--accent:          var(--gray-900);')
        ->toContain('--brand:           var(--blue-500);')
        ->toContain('--ai-accent:       var(--indigo-500);')
        ->toContain('--border-selected: var(--gray-900);')
        ->not->toContain('--accent:          var(--teal-');
});

test('the latest Jaunt geometry elevation and material tokens are installed', function () {
    expect(File::get(base_path('vendor/pilotcms/core/resources/css/jaunt/tokens/radius.css')))
        ->toContain('--radius-xl:   16px;')
        ->toContain('--radius-2xl:  24px;');

    expect(File::get(base_path('vendor/pilotcms/core/resources/css/jaunt/tokens/spacing.css')))
        ->toContain('--sidebar-w:      300px;')
        ->toContain('--control-h:      36px;');

    expect(File::get(base_path('vendor/pilotcms/core/resources/css/app.css')))
        ->toContain("@import './jaunt/tokens/materials.css';")
        ->toContain('background: var(--material-chrome-bg);')
        ->toContain('--color-slate-900: var(--gray-900);');
});

test('the design-system update keeps the existing application stack', function () {
    $package = json_decode(File::get(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($package['dependencies'])
        ->toHaveKeys(['tailwindcss', 'vite', 'lucide'])
        ->not->toHaveKey('react')
        ->not->toHaveKey('react-dom');

    expect($composer['require'])
        ->toHaveKeys(['laravel/framework', 'livewire/livewire']);
});

test('the application shell follows the current Jaunt component contracts', function () {
    $coreResources = base_path('vendor/pilotcms/core/resources');
    $styles = File::get($coreResources.'/css/app.css');
    $base = File::get($coreResources.'/css/jaunt/tokens/base.css');
    $palette = File::get($coreResources.'/views/livewire/admin/command-palette.blade.php');
    $editor = File::get($coreResources.'/views/livewire/admin/content/editor.blade.php');
    $contentIndex = File::get($coreResources.'/views/livewire/admin/content/index.blade.php');
    $views = collect(File::allFiles($coreResources.'/views'))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file): string => File::get($file->getPathname()))
        ->implode("\n");

    expect($styles)
        ->toContain('height: 38px;')
        ->toContain('height: 46px;')
        ->toContain('border-radius: var(--radius-full);')
        ->not->toContain('teal');

    expect($palette)
        ->toContain('max-w-[600px]')
        ->toContain('bg-overlay')
        ->toContain('min-h-[46px]')
        ->toContain('<x-jaunt.icon :name="$result[\'icon\']" size="sm" />')
        ->not->toContain('class="ph');

    expect($views)->not->toContain('teal');
    expect($views)->not->toContain('phosphor-icons')->not->toContain('class="ph');

    expect($base)
        ->toContain('a { color: inherit; text-decoration: none; }')
        ->toContain('a:hover { text-decoration: none; }');

    expect($views)->not->toContain('hover:underline');
    expect($editor)
        ->toContain('bg-selected text-primary shadow-[inset_2px_0_0_var(--accent)]')
        ->not->toContain('bg-blue-50 text-blue-700 border border-blue-100 shadow-sm');

    expect($contentIndex)
        ->toContain('<x-jaunt.shell.dynamic-header')
        ->toContain('as="header"')
        ->toContain('scroll-target="#content-list-scroll"')
        ->not->toContain('<header class="cms-topbar"');
});

test('admin interfaces follow the Interface Craft means and methods contract', function () {
    $coreViews = base_path('vendor/pilotcms/core/resources/views');
    $adminViews = collect(File::allFiles($coreViews.'/livewire/admin'))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file): string => File::get($file->getPathname()))
        ->implode("\n");
    $philosophy = File::get(base_path('vendor/pilotcms/core/resources/design-system/docs/11-interface-craft-means-and-methods.md'));

    expect($adminViews)
        ->not->toContain('transition-all')
        ->not->toContain('duration-200');

    expect($philosophy)
        ->toContain('Reduce until it is clear')
        ->toContain('Make motion communicate')
        ->toContain('Adopting this philosophy must not replace or bypass those tools');

    foreach ([
        $coreViews.'/livewire/admin/content/create.blade.php',
        $coreViews.'/livewire/admin/content/edit.blade.php',
        $coreViews.'/livewire/admin/spaces/create.blade.php',
        $coreViews.'/livewire/admin/spaces/edit.blade.php',
    ] as $formView) {
        expect(File::get($formView))
            ->toContain('wire:loading.attr="disabled"')
            ->toContain('wire:target="save"');
    }
});

test('every scrollable admin page uses the shared dynamic header', function () {
    $pageViews = [
        'livewire/admin/assets/index.blade.php',
        'livewire/admin/blocks/create.blade.php',
        'livewire/admin/blocks/edit.blade.php',
        'livewire/admin/blocks/index.blade.php',
        'livewire/admin/content-types/index.blade.php',
        'livewire/admin/content/create.blade.php',
        'livewire/admin/content/edit.blade.php',
        'livewire/admin/content/index.blade.php',
        'livewire/admin/dashboard.blade.php',
        'livewire/admin/datasources/index.blade.php',
        'livewire/admin/settings/index.blade.php',
        'livewire/admin/spaces/create.blade.php',
        'livewire/admin/spaces/edit.blade.php',
        'livewire/admin/spaces/index.blade.php',
        'livewire/admin/users/index.blade.php',
        'pages/settings/⚡appearance.blade.php',
        'pages/settings/⚡password.blade.php',
        'pages/settings/⚡profile.blade.php',
        'pages/settings/⚡two-factor.blade.php',
    ];

    foreach ($pageViews as $pageView) {
        $path = base_path("vendor/pilotcms/core/resources/views/{$pageView}");

        expect(File::get($path))
            ->toContain('<x-jaunt.shell.dynamic-header')
            ->toContain('as="header"')
            ->toContain('scroll-target=')
            ->not->toContain('class="cms-topbar"');
    }
});
