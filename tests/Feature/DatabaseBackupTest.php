<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    public function test_sqlite_database_backup_creates_a_restorable_snapshot(): void
    {
        $originalDatabase = config('database.connections.sqlite.database');
        $source = storage_path('app/testing-backup-source.sqlite');
        $backup = null;

        try {
            File::delete($source);
            File::put($source, '');
            config(['database.connections.sqlite.database' => $source]);
            DB::purge('sqlite');
            DB::statement('create table backup_probe (id integer primary key, label varchar not null)');
            DB::table('backup_probe')->insert(['label' => 'snapshot-ok']);

            $existingBackups = File::glob(storage_path('app/backups/database-*.sqlite'));
            $this->artisan('database:backup')->assertSuccessful();
            $newBackups = array_values(array_diff(
                File::glob(storage_path('app/backups/database-*.sqlite')),
                $existingBackups,
            ));
            $backup = $newBackups[0] ?? null;

            $this->assertNotNull($backup);
            $this->assertGreaterThan(0, File::size($backup));

            config(['database.connections.sqlite.database' => $backup]);
            DB::purge('sqlite');

            $this->assertSame('snapshot-ok', DB::table('backup_probe')->value('label'));
        } finally {
            config(['database.connections.sqlite.database' => $originalDatabase]);
            DB::purge('sqlite');
            File::delete($source);

            if ($backup !== null) {
                File::delete($backup);
            }
        }
    }
}
