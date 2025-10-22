<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup 
        {--dir= : relative dir inside storage/app to save backups (default: backups)}
        {--keep=3 : how many backups to keep} 
        {--ssl-ca= : optional path to DB CA file on server for SSL connections}';

    protected $description = 'Create a mysqldump backup and store it in storage/app/{dir} as .sql.gz';

    public function handle()
    {
        $this->info('Starting DB backup...');

        $dbHost = env('DB_HOST');
        $dbPort = env('DB_PORT', 3306);
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        if (!$dbName || !$dbUser) {
            $this->error('DB_DATABASE or DB_USERNAME not set in environment.');
            return 1;
        }

        $dir = $this->option('dir') ?: 'backups';
        $keep = (int) $this->option('keep');
        $sslCa = $this->option('ssl-ca');

        $storagePath = storage_path('app/' . $dir);
        if (!is_dir($storagePath) && !mkdir($storagePath, 0700, true)) {
            $this->error("Failed to create backup dir: {$storagePath}");
            return 1;
        }

        $timestamp = date('Ymd-His');
        $filename = "{$dbName}-{$timestamp}.sql.gz";
        $filepath = $storagePath . DIRECTORY_SEPARATOR . $filename;

        // create temporary defaults file (to avoid exposing password in ps)
        $defaultsPath = $storagePath . DIRECTORY_SEPARATOR . 'my.cnf.' . Str::random(8);
        $defaultsContent = "[client]\nuser={$dbUser}\npassword={$dbPass}\nhost={$dbHost}\nport={$dbPort}\n";
        file_put_contents($defaultsPath, $defaultsContent);
        chmod($defaultsPath, 0600);

        // build mysqldump command
        $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump'); // if custom path set in .env
        $options = [
            "--defaults-extra-file=" . escapeshellarg($defaultsPath),
            '--single-transaction',
            '--quick',
            '--max-allowed-packet=1G',
            // '--skip-lock-tables' // uncomment if you want to avoid table locks (but may affect consistency)
        ];

        // add SSL options if requested
        if ($sslCa) {
            $options[] = '--ssl-mode=REQUIRED';
            $options[] = '--ssl-ca=' . escapeshellarg($sslCa);
        }

        $cmd = vsprintf('%s %s %s 2>&1 | gzip > %s', [
            $mysqldump,
            implode(' ', $options),
            escapeshellarg($dbName),
            escapeshellarg($filepath),
        ]);

        $this->info("Running: mysqldump -> {$filename}");
        exec($cmd, $output, $returnVar);

        // remove defaults file
        @unlink($defaultsPath);

        if ($returnVar !== 0 || !file_exists($filepath)) {
            $this->error("Backup failed (code {$returnVar}). Output:");
            $this->line(implode("\n", $output));
            if (file_exists($filepath)) {
                $this->line("Partial file at: {$filepath} (size: " . filesize($filepath) . ")");
            }
            return 1;
        }

        $this->info("Backup created: storage/app/{$dir}/{$filename} (" . round(filesize($filepath)/1024/1024, 2) . " MB)");

        // rotate old files
        if ($keep > 0) {
            $files = collect(scandir($storagePath))
                ->filter(fn($f) => preg_match('/\.sql\.gz$/', $f))
                ->sortDesc()
                ->values();

            if ($files->count() > $keep) {
                $files->slice($keep)->each(function ($f) use ($storagePath) {
                    @unlink($storagePath . DIRECTORY_SEPARATOR . $f);
                });
                $this->info('Old backups rotated, keeping last ' . $keep);
            }
        }

        return 0;
    }
}
