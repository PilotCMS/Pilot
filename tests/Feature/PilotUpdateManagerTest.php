<?php

use Illuminate\Filesystem\Filesystem;
use Pilot\Core\Support\Updates\PilotUpdateManager;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->fixture = storage_path('framework/testing/pilot-update-manager-'.bin2hex(random_bytes(6)));
    $this->application = $this->fixture.'/application';
    $this->storage = $this->fixture.'/storage';
    $this->files->ensureDirectoryExists($this->application);
    $this->files->ensureDirectoryExists($this->storage);
    $this->files->put($this->application.'/composer.json', "{\n    \"name\": \"pilot/test\"\n}\n");
    $this->files->put($this->application.'/composer.lock', "{\n    \"packages\": []\n}\n");

    (new Process(['git', 'init', '--quiet'], $this->application))->mustRun();
    (new Process(['git', 'add', 'composer.json', 'composer.lock'], $this->application))->mustRun();
    (new Process([
        'git',
        '-c', 'user.name=Pilot Tests',
        '-c', 'user.email=pilot@example.test',
        'commit', '--quiet', '-m', 'Initial files',
    ], $this->application))->mustRun();

    $this->manager = new PilotUpdateManager($this->files, $this->application, $this->storage);
});

afterEach(function () {
    $this->files->deleteDirectory($this->fixture);
});

it('allows clean Composer files', function () {
    $this->manager->assertComposerFilesAreSafe();

    expect(true)->toBeTrue();
});

it('allows dirty Composer files when their exact hashes were written by the updater', function () {
    $this->files->put($this->application.'/composer.lock', "{\n    \"packages\": [{\"name\": \"pilotcms/core\"}]\n}\n");
    $this->manager->markComposerFilesAsUpdaterOwned();

    $this->manager->assertComposerFilesAreSafe();

    expect(true)->toBeTrue();
});

it('rejects a user edit after the updater records its Composer files', function () {
    $this->files->put($this->application.'/composer.lock', "{\n    \"packages\": [{\"name\": \"pilotcms/core\"}]\n}\n");
    $this->manager->markComposerFilesAsUpdaterOwned();
    $this->files->append($this->application.'/composer.json', "\n");

    expect(fn () => $this->manager->assertComposerFilesAreSafe())
        ->toThrow(RuntimeException::class, 'changes that were not made by the Pilot updater');
});

it('uses updater ownership to reject unrecognized changes when Git is unavailable', function () {
    $this->files->put($this->application.'/composer.lock', "{\n    \"packages\": [{\"name\": \"pilotcms/core\"}]\n}\n");
    $this->manager->markComposerFilesAsUpdaterOwned();
    $this->files->deleteDirectory($this->application.'/.git');
    $this->files->append($this->application.'/composer.json', "\n");

    expect(fn () => $this->manager->assertComposerFilesAreSafe())
        ->toThrow(RuntimeException::class, 'host without Git');
});

it('stops when Composer files change after an update is queued', function () {
    $statePath = $this->storage.'/app/pilot/update.json';
    $this->files->ensureDirectoryExists(dirname($statePath));
    $this->files->put($statePath, json_encode([
        'status' => 'queued',
        'preflight_composer_files' => [
            'composer.json' => hash_file('sha256', $this->application.'/composer.json'),
            'composer.lock' => hash_file('sha256', $this->application.'/composer.lock'),
        ],
    ], JSON_THROW_ON_ERROR));

    $this->manager->assertComposerFilesUnchanged();
    $this->files->append($this->application.'/composer.lock', "\n");

    expect(fn () => $this->manager->assertComposerFilesUnchanged())
        ->toThrow(RuntimeException::class, 'changed after this update was requested');
});
