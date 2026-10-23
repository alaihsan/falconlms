<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->target = sys_get_temp_dir().'/falcon-backup-'.uniqid();
    config([
        'falcon.backup.path' => $this->target,
        'falcon.backup.connection' => 'pgsql',
        'database.connections.pgsql.database' => 'falcon',
        'database.connections.pgsql.username' => 'falcon',
        'database.connections.pgsql.password' => 'rahasia',
    ]);
});

afterEach(fn () => File::deleteDirectory($this->target));

test('the backup dumps the database, copies the files and prunes old dumps', function () {
    Process::fake();
    File::ensureDirectoryExists($this->target.'/database');
    $old = $this->target.'/database/falcon-20200101-020000.dump';
    File::put($old, 'old');
    touch($old, now()->subDays(30)->getTimestamp());

    $this->artisan('falcon:backup')->assertSuccessful();

    Process::assertRan(fn ($process) => $process->command[0] === 'pg_dump'
        && in_array('--format=custom', $process->command, true)
        && end($process->command) === 'falcon'
        && $process->environment['PGPASSWORD'] === 'rahasia');
    Process::assertRan(fn ($process) => $process->command[0] === 'rsync');

    expect(File::exists($old))->toBeFalse();
});

test('a failed dump stops the backup', function () {
    Process::fake(['*' => Process::result(errorOutput: 'connection refused', exitCode: 1)]);

    $this->artisan('falcon:backup')->assertFailed();

    Process::assertRanTimes(fn ($process) => $process->command[0] === 'rsync', 0);
});

test('the backup needs a target folder', function () {
    config(['falcon.backup.path' => null]);

    $this->artisan('falcon:backup')->assertFailed();
});

test('the backup is scheduled every night', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('falcon:backup')->assertSuccessful();
});
