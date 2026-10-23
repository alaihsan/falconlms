<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'falcon:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Back up the database and uploaded files to FALCON_BACKUP_PATH';

    /**
     * Writes a PostgreSQL dump (pg_dump custom format, restore with pg_restore)
     * and copies uploaded files with rsync. Dumps older than
     * FALCON_BACKUP_KEEP_DAYS are removed; copied files are never deleted
     * from the backup, so a file removed by mistake can still be recovered.
     */
    public function handle(): int
    {
        $target = config('falcon.backup.path');

        if (! is_string($target) || $target === '') {
            $this->components->error('Set FALCON_BACKUP_PATH (for example a disk other than the one with the files).');

            return self::FAILURE;
        }

        /** @var array{driver?: string, host: string, port: string|int, database: string, username: string, password: string|null}|null $db */
        $db = config('database.connections.'.config('falcon.backup.connection'));

        if (! is_array($db) || ($db['driver'] ?? null) !== 'pgsql') {
            $this->components->error('Backups support the PostgreSQL database only.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($target.'/database');
        File::ensureDirectoryExists($target.'/files');

        $dump = $target.'/database/falcon-'.now()->format('Ymd-His').'.dump';

        $result = Process::timeout(3600)
            ->env(['PGPASSWORD' => (string) $db['password']])
            ->run([
                config('falcon.backup.pg_dump'),
                '--format=custom',
                '--host='.$db['host'],
                '--port='.$db['port'],
                '--username='.$db['username'],
                '--file='.$dump,
                $db['database'],
            ]);

        if ($result->failed()) {
            File::delete($dump);
            $this->components->error('pg_dump failed: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        $files = Process::timeout(3600)->run([
            config('falcon.backup.rsync'),
            '--archive',
            rtrim(Storage::disk('local')->path(''), '/').'/',
            $target.'/files/',
        ]);

        if ($files->failed()) {
            $this->components->error('rsync failed: '.trim($files->errorOutput()));

            return self::FAILURE;
        }

        $this->pruneOldDumps($target.'/database');

        $this->components->info('Backup written to '.$target);

        return self::SUCCESS;
    }

    private function pruneOldDumps(string $directory): void
    {
        $cutoff = now()->subDays(config('falcon.backup.keep_days'))->getTimestamp();

        foreach (File::glob($directory.'/falcon-*.dump') as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
            }
        }
    }
}
