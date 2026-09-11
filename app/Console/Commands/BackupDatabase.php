<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--disk= : Target disk (default: BACKUP_DISK env)}';

    protected $description = 'Backup database ke storage lokal atau cloud dan hapus backup lama';

    public function handle(): int
    {
        $disk = $this->option('disk') ?: env('BACKUP_DISK', 'local');
        $retainDays = (int) env('BACKUP_RETAIN_DAYS', 7);
        $filename = 'backups/db-'.now()->format('Y-m-d_His').'.sql';

        $dbConnection = config('database.default');
        $dbConfig = config('database.connections.'.$dbConnection);

        if ($dbConfig['driver'] !== 'mysql') {
            $this->error('Backup saat ini hanya mendukung MySQL/MariaDB.');
            return self::FAILURE;
        }

        $host = escapeshellarg($dbConfig['host']);
        $port = escapeshellarg($dbConfig['port'] ?? '3306');
        $user = escapeshellarg($dbConfig['username']);
        $pass = $dbConfig['password'];
        $dbName = escapeshellarg($dbConfig['database']);

        $passArg = $pass ? '-p'.escapeshellarg($pass) : '';
        $tmpPath = storage_path('app/backups/tmp_'.uniqid().'.sql');

        // Pastikan direktori ada
        @mkdir(dirname($tmpPath), 0755, true);

        $cmd = "mysqldump --host={$host} --port={$port} --user={$user} {$passArg} {$dbName} > ".escapeshellarg($tmpPath)." 2>&1";
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($tmpPath)) {
            $this->error('mysqldump gagal: '.implode("\n", $output));
            @unlink($tmpPath);
            return self::FAILURE;
        }

        // Upload ke disk target
        $contents = file_get_contents($tmpPath);
        @unlink($tmpPath);

        Storage::disk($disk)->put($filename, $contents);
        $this->info('Backup disimpan: '.$filename.' di disk ['.$disk.']');

        // Hapus backup lama
        $cutoff = now()->subDays($retainDays)->timestamp;
        $files = Storage::disk($disk)->files('backups');
        $deleted = 0;
        foreach ($files as $file) {
            if (Storage::disk($disk)->lastModified($file) < $cutoff) {
                Storage::disk($disk)->delete($file);
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $this->info("Menghapus {$deleted} backup lama (>{$retainDays} hari).");
        }

        return self::SUCCESS;
    }
}
