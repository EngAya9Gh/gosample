<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DatabaseBackup extends Command
{
    protected $signature = 'db:backup {--ssl : Use SSL mode if DB requires it}';
    protected $description = 'Create a compressed MySQL backup and store it in storage/app/public/backups';

    public function handle()
    {
        $this->info("Starting DB backup...");

        // ✅ نقرأ من config بدل env (حتى تعمل مع config:cache)
        $connection = config('database.connections.mysql');
        $dbHost     = $connection['host']     ?? null;
        $dbPort     = $connection['port']     ?? 3306;
        $dbUser     = $connection['username'] ?? null;
        $dbPass     = $connection['password'] ?? null;
        $dbName     = $connection['database'] ?? null;

        if (!$dbName || !$dbUser) {
            $this->error("DB config missing!");
            Log::error("db:backup failed - DB_DATABASE or DB_USERNAME missing");
            return 1;
        }

        $backupPath = storage_path('app/public/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0775, true);
        }

        $timestamp = date('Ymd-His');
        $filename  = "{$dbName}-{$timestamp}.sql.gz";
        $filepath  = $backupPath . DIRECTORY_SEPARATOR . $filename;

        // ✅ defaults-extra-file حتى لا يظهر الباسورد في ps
        $defaultsFile = $backupPath . DIRECTORY_SEPARATOR . 'my.cnf.' . Str::random(8);
        file_put_contents($defaultsFile, "[client]\nuser={$dbUser}\npassword={$dbPass}\nhost={$dbHost}\nport={$dbPort}\n");
        chmod($defaultsFile, 0600);

        // ✅ تحديد مسار mysqldump (مرن)
        $mysqldumpCandidates = [
            'mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        $mysqldump = null;
        foreach ($mysqldumpCandidates as $candidate) {
            if (is_executable($candidate) || shell_exec("which $candidate")) {
                $mysqldump = $candidate;
                break;
            }
        }

        if (!$mysqldump) {
            unlink($defaultsFile);
            $this->error("mysqldump not found on server!");
            Log::error("db:backup failed - mysqldump not found");
            return 1;
        }

        // ✅ بناء الأمر
        $cmd = "$mysqldump --defaults-extra-file={$defaultsFile} --single-transaction --quick --max-allowed-packet=1G {$dbName}";

        // إذا DigitalOcean requires SSL
        if ($this->option('ssl')) {
            $cmd .= " --ssl-mode=REQUIRED";
        }

        // Pipe to gzip
        $cmd .= " | gzip > " . escapeshellarg($filepath) . " 2>&1";

        $output = [];
        exec($cmd, $output, $returnVar);

        // حذف ملف .cnf
        unlink($defaultsFile);

        if ($returnVar !== 0) {
            $this->error("Backup failed!");
            Log::error("db:backup failed", [
                'output' => $output
            ]);
            return 1;
        }

        $size = round(filesize($filepath) / 1024 / 1024, 2); // MB
        $this->info("✅ Backup finished");
        $this->info("File: {$filename} ({$size} MB)");
        $this->info("Location: storage/app/public/backups/");
        $this->info("Public URL: /storage/backups/{$filename}");

        Log::info("db:backup success", [
            'file' => $filename,
            'size_mb' => $size
        ]);

        return 0;
    }
}
