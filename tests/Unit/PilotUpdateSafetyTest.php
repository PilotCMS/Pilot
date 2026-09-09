<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Pilot\Core\Support\Updates\PilotUpdateSafety;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->files = new Filesystem;
    $this->fixture = storage_path('framework/testing/pilot-update-safety-'.bin2hex(random_bytes(6)));
    $this->application = $this->fixture.'/application';
    $this->storage = $this->fixture.'/storage';
    $this->database = $this->fixture.'/pilot.sqlite';
    $this->files->ensureDirectoryExists($this->application);
    $this->files->ensureDirectoryExists($this->storage);
    $this->files->put($this->application.'/composer.json', "{\n    \"name\": \"pilot/test\"\n}\n");
    $this->files->put($this->application.'/composer.lock', "{\n    \"packages\": []\n}\n");
    $this->files->put($this->application.'/composer.php', "<?php exit(0);\n");
    $this->files->put($this->application.'/artisan', "<?php exit(0);\n");
    touch($this->database);

    $this->originalDatabase = config('database.default');
    config([
        'database.default' => 'pilot_update_test',
        'database.connections.pilot_update_test' => [
            'driver' => 'sqlite',
            'database' => $this->database,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'cms.updates.database_backup' => true,
        'cms.updates.backup_retention' => 3,
    ]);
    DB::purge('pilot_update_test');
    DB::connection('pilot_update_test')->statement('create table update_test (value varchar not null)');
    DB::connection('pilot_update_test')->table('update_test')->insert(['value' => 'before']);

    $this->safety = new PilotUpdateSafety(
        $this->files,
        $this->application,
        $this->storage,
        ['composer' => $this->application.'/composer.php'],
    );
});

afterEach(function () {
    DB::purge('pilot_update_test');
    config(['database.default' => $this->originalDatabase]);
    $this->files->deleteDirectory($this->fixture);
});

it('backs up and restores Composer files and a SQLite database', function () {
    $backup = $this->safety->createBackup('test-update');

    $this->files->put($this->application.'/composer.json', "{\n    \"name\": \"changed/project\"\n}\n");
    DB::connection('pilot_update_test')->table('update_test')->update(['value' => 'after']);

    $log = '';
    $this->safety->rollback($backup, function (string $output) use (&$log): void {
        $log .= $output;
    });

    expect($this->files->get($this->application.'/composer.json'))
        ->toContain('pilot/test');
    expect(DB::connection('pilot_update_test')->table('update_test')->value('value'))
        ->toBe('before');
    expect($log)->toContain('Restoring Composer files')->toContain('Restoring the database');
});
