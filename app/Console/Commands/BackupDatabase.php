<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

#[Signature('database:backup')]
#[Description('Create a consistent SQLite database backup')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('Database backup command supports SQLite only.');

            return self::FAILURE;
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/database-'.now()->format('Ymd_His').'.sqlite';

        if (File::exists($path)) {
            $this->error("Backup file already exists: {$path}");

            return self::FAILURE;
        }

        try {
            $quotedPath = DB::connection()->getPdo()->quote($path);
            DB::statement('VACUUM INTO '.$quotedPath);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Database backup failed.');

            return self::FAILURE;
        }

        if (! File::exists($path) || File::size($path) === 0) {
            $this->error('SQLite backup file was not created.');

            return self::FAILURE;
        }

        $this->info("Database backup created: {$path}");

        return self::SUCCESS;
    }
}
