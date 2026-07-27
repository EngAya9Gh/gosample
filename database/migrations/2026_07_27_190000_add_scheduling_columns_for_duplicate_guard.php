<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the scheduled-task duplicate guard depends on.
 *
 *   scheduled_tasks.last_generated_at  - the day-claim used by CheckScheduledTasks
 *   tasks.scheduled_task_id            - links a generated task to its schedule
 *
 * Both already exist on some environments (they were added by hand, without a
 * migration file), so every step is guarded. Running this where they are
 * already present is a no-op.
 *
 * Cost on a large tasks table: adding a nullable column at the end is an
 * INSTANT operation on MySQL 8+, and the index is built online (INPLACE).
 * No table rebuild, no long lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('scheduled_tasks', 'last_generated_at')) {
            Schema::table('scheduled_tasks', function (Blueprint $table) {
                $table->timestamp('last_generated_at')->nullable()
                    ->comment('Last date this schedule produced a task; the duplicate guard claims it.');
            });
        }

        if (! Schema::hasColumn('tasks', 'scheduled_task_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('scheduled_task_id')->nullable()
                    ->comment('The ScheduledTask that generated this task; null for manual tasks.');
            });
        }

        if (! $this->hasIndex('tasks', 'tasks_scheduled_task_created_idx')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['scheduled_task_id', 'created_at'], 'tasks_scheduled_task_created_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('tasks', 'tasks_scheduled_task_created_idx')) {
            Schema::table('tasks', fn (Blueprint $table) => $table->dropIndex('tasks_scheduled_task_created_idx'));
        }

        if (Schema::hasColumn('tasks', 'scheduled_task_id')) {
            Schema::table('tasks', fn (Blueprint $table) => $table->dropColumn('scheduled_task_id'));
        }

        if (Schema::hasColumn('scheduled_tasks', 'last_generated_at')) {
            Schema::table('scheduled_tasks', fn (Blueprint $table) => $table->dropColumn('last_generated_at'));
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getConnection()
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))
            ->isNotEmpty();
    }
};
