<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Copies the SQLite database to storage/app/backups (outside the web root)
 * with VACUUM INTO, which is consistent even while staff are writing — a
 * plain file copy of a WAL-mode database can miss un-checkpointed changes.
 * Scheduled nightly in routes/console.php; run by hand before a deploy.
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db {--keep=14 : How many daily backups to retain}';

    protected $description = 'Back up the SQLite database to storage/app/backups';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('app:backup-db only supports the sqlite connection.');
            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $target = $dir . DIRECTORY_SEPARATOR . 'database-' . now()->format('Ymd-His') . '.sqlite';

        DB::statement('VACUUM INTO ?', [$target]);

        $this->info('Backup written: ' . $target . ' (' . number_format(filesize($target) / 1024) . ' KB)');

        $keep = max(1, (int) $this->option('keep'));
        $old = collect(File::glob($dir . DIRECTORY_SEPARATOR . 'database-*.sqlite'))
            ->sortDesc()
            ->slice($keep);

        foreach ($old as $stale) {
            File::delete($stale);
            $this->line('Removed old backup: ' . basename($stale));
        }

        return self::SUCCESS;
    }
}
