<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;

class DatabaseBackupStream extends Command
{
    protected $signature = 'db:backup-stream';
    protected $description = 'Stream MySQL backup to storage/app/public/backups with progress (no mysqldump).';

    public function handle()
    {
        $this->info("🚀 Starting streaming DB backup (PHP mode, no mysqldump)...");

        @set_time_limit(0);
        ini_set('memory_limit', '-1');
        ini_set('output_buffering', '0');

        $connection = config('database.connections.mysql');
        $dbHost     = $connection['host'] ?? null;
        $dbPort     = $connection['port'] ?? 3306;
        $dbUser     = $connection['username'] ?? null;
        $dbPass     = $connection['password'] ?? null;
        $dbName     = $connection['database'] ?? null;

        if (!$dbHost || !$dbUser || !$dbName) {
            $this->error("Missing DB config from config(database.connections.mysql)");
            return 1;
        }

        $backupDir = storage_path('app/public/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $timestamp = date('Ymd-His');
        $filename  = "{$dbName}-{$timestamp}.sql.gz";
        $filepath  = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $gz = \gzopen($filepath, 'w9');
        if (!$gz) {
            $this->error("Failed to open backup file for writing: $filepath");
            return 1;
        }

        $w = function($s) use ($gz) {
            \gzwrite($gz, $s);
        };

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ];

        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        } catch (PDOException $e) {
            \gzclose($gz);
            @unlink($filepath);
            $this->error("PDO connection failed: " . $e->getMessage());
            return 1;
        }

        // File header
        $w("-- DB backup (PHP streaming mode)\n-- Database: `{$dbName}`\n-- Generated: ".date('c')."\n\n");
        $w("SET FOREIGN_KEY_CHECKS=0;\n");

        // Get list of base tables
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        while ($r = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $r[0];
        }

        $totalTables = count($tables);
        $batchSize   = 500;

        foreach ($tables as $i => $table) {
            $this->info("📦 Dumping table " . ($i+1) . "/$totalTables: $table");

            $w("\n-- --------------------------------\n");
            $w("-- Structure for `$table`\n");
            $w("-- --------------------------------\n");
            $w("DROP TABLE IF EXISTS `$table`;\n");

            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $w($create['Create Table'].";\n\n");

            $w("-- Data for `$table`\n");

            // Columns
            $cols = array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
            $colList = "`".implode("`,`", $cols)."`";

            // Fetch rows
            $sel = $pdo->query("SELECT * FROM `$table`");
            $batch = [];

            while ($row = $sel->fetch(PDO::FETCH_NUM)) {
                foreach ($row as &$v) {
                    $v = $v === null ? 'NULL' : $pdo->quote($v);
                }
                $batch[] = '('.implode(',', $row).')';

                if (count($batch) >= $batchSize) {
                    $w("INSERT INTO `$table` ($colList) VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                    @fflush($gz);
                }
            }

            // Remainder
            if (!empty($batch)) {
                $w("INSERT INTO `$table` ($colList) VALUES\n".implode(",\n", $batch).";\n");
                @fflush($gz);
            }
        }

        $w("SET FOREIGN_KEY_CHECKS=1;\n");
        \gzclose($gz);

        $this->info("✅ Backup complete");
        $this->info("📁 Saved to: $filepath");
        $this->info("🌐 Download: /storage/backups/{$filename}");

        return 0;
    }
}
