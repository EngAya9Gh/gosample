<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indexes for queries that were reading whole tables.
 *
 *   tasks (created_at)                  - date-range filters on the admin task lists,
 *                                         the delayed-tasks dashboard, header dropdown
 *   tasks (collection_date)             - taskDelayed:cron pickup check, attendance:calc-auto
 *   tasks (close_date)                  - taskDelayed:cron drop-off check
 *   attendances (driver_id, created_at) - attendance:check-late, every minute for
 *                                         every driver on shift
 *
 * tasks had single-column indexes on created_at and collection_date until
 * 2026_06_24_190526 dropped them as "orphaned" once deleted_at was removed from
 * their composites. The composites that remain - (status, created_at) and
 * (driver_id, collection_date) - only serve queries that also filter on their
 * leading column, which the queries above do not.
 *
 * An index is skipped when one already starts with the same columns, so this is
 * a no-op where they exist (some environments have hand-made indexes).
 *
 * Production safety: ALGORITHM=INPLACE, LOCK=NONE builds online and makes MySQL
 * error out instead of silently falling back to a locking build. lock_wait_timeout
 * is lowered so that, if a long transaction holds the table's metadata lock, the
 * migration fails fast instead of queueing every query on the table behind it.
 * Re-running after such a failure is safe.
 */
return new class extends Migration
{
    private const INDEXES = [
        'tasks' => [
            'tasks_created_at_idx'      => ['created_at'],
            'tasks_collection_date_idx' => ['collection_date'],
            'tasks_close_date_idx'      => ['close_date'],
        ],
        'attendances' => [
            'attendances_driver_created_idx' => ['driver_id', 'created_at'],
        ],
    ];

    public function up(): void
    {
        $previousTimeout = DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value;
        DB::statement('SET SESSION lock_wait_timeout = 10');

        try {
            foreach (self::INDEXES as $table => $indexes) {
                $clauses = [];

                foreach ($indexes as $name => $columns) {
                    if ($this->hasIndexStartingWith($table, $columns)) {
                        continue;
                    }

                    $clauses[] = sprintf(
                        'ADD INDEX `%s` (%s)',
                        $name,
                        implode(', ', array_map(fn ($column) => "`{$column}`", $columns))
                    );
                }

                if ($clauses) {
                    // One ALTER per table so all its new indexes are built in a single pass.
                    DB::statement("ALTER TABLE `{$table}` " . implode(', ', $clauses) . ', ALGORITHM=INPLACE, LOCK=NONE');
                }
            }
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = ' . (int) $previousTimeout);
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if ($this->hasIndex($table, $name)) {
                    DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                }
            }
        }
    }

    /**
     * True when some index has exactly these columns as its leading key parts,
     * e.g. an existing (driver_id, created_at, shift_id) covers (driver_id, created_at).
     */
    private function hasIndexStartingWith(string $table, array $columns): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->groupBy('Key_name')
            ->contains(fn ($parts) => $parts->sortBy('Seq_in_index')
                ->pluck('Column_name')
                ->take(count($columns))
                ->values()
                ->all() === $columns);
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))
            ->isNotEmpty();
    }
};
