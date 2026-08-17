<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Pilot\Core\Livewire\Admin\Content\Editor;
use Pilot\Core\Support\Installation\HostSynchronizer;

test('core owns the versioned admin application surface', function () {
    expect(Route::getRoutes()->getByName('admin.content.editor')?->getActionName())
        ->toBe(Editor::class);

    expect(View::getFinder()->find('livewire.admin.content.editor'))
        ->toStartWith(realpath(base_path('vendor/pilotcms/core')).'/resources/views');

    expect(app_path('Livewire/Admin/Content/Editor.php'))->not->toBeFile();
    expect(resource_path('views/livewire/admin/content/editor.blade.php'))->not->toBeFile();
});

test('legacy hosts can be migrated to package owned routes and assets idempotently', function () {
    $files = new Filesystem;
    $host = storage_path('framework/testing/pilot-host-'.bin2hex(random_bytes(4)));

    try {
        $files->ensureDirectoryExists($host.'/bootstrap');
        $files->ensureDirectoryExists($host.'/routes');
        $files->ensureDirectoryExists($host.'/resources/css');
        $files->ensureDirectoryExists($host.'/resources/js');

        $files->put($host.'/bootstrap/app.php', "<?php\n        api: __DIR__.'/../routes/api.php',\n");
        $files->put($host.'/bootstrap/providers.php', "<?php\nreturn [\n    Tweaker\\TweakerServiceProvider::class,\n];\n");
        $files->put($host.'/routes/web.php', "<?php\nrequire __DIR__.'/admin.php';\n");
        $files->put($host.'/resources/css/app.css', 'legacy css');
        $files->put($host.'/resources/js/app.js', 'legacy js');

        $synchronizer = new HostSynchronizer($files);

        expect($synchronizer->sync($host))->toHaveCount(7);
        expect($synchronizer->sync($host))->toBe([]);
        expect($files->get($host.'/bootstrap/app.php'))->not->toContain("routes/api.php");
        expect($files->get($host.'/bootstrap/providers.php'))->not->toContain('Tweaker');
        expect($files->get($host.'/routes/web.php'))->not->toContain("routes/admin.php");
        expect($files->get($host.'/resources/css/app.css'))->toContain('vendor/pilotcms/core/resources/css/app.css');
        expect($files->get($host.'/resources/js/app.js'))->toContain('vendor/pilotcms/core/resources/js/app.js');
        expect($files->get($host.'/package.json'))->toContain('"build": "vite build"');
        expect($files->get($host.'/package-lock.json'))->toContain('"lockfileVersion": 3');
    } finally {
        $files->deleteDirectory($host);
    }
});
