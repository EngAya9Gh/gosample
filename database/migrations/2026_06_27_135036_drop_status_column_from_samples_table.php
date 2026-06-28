<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // To ensure this migration runs flawlessly on BOTH Test and Production servers
        // (even if they have different index states from previous partial migrations),
        // we first fetch the existing indexes natively.
        $existingIndexes = collect(DB::select('SHOW INDEX FROM samples'))->pluck('Key_name')->unique()->toArray();

        $existingFks = collect(DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME')->toArray();

        // Step 1: Drop foreign keys safely
        Schema::table('samples', function (Blueprint $table) use ($existingFks) {
            if (in_array('samples_new_task_fk', $existingFks)) {
                $table->dropForeign('samples_new_task_fk');
            }
            if (in_array('samples_new_location_fk', $existingFks)) {
                $table->dropForeign('samples_new_location_fk');
            }
        });

        // Step 2: Drop indexes ONLY if they exist on this specific server
        Schema::table('samples', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('idx_samples_task_status', $existingIndexes)) {
                $table->dropIndex('idx_samples_task_status');
            }
            if (in_array('idx_samples_location_status', $existingIndexes)) {
                $table->dropIndex('idx_samples_location_status');
            }
            if (in_array('samples_task_deleted_idx', $existingIndexes)) {
                $table->dropIndex('samples_task_deleted_idx');
            }
            if (in_array('idx_samples_created_at', $existingIndexes)) {
                $table->dropIndex('idx_samples_created_at');
            }
            
            // Re-create lean standalone indexes if they don't exist
            if (!in_array('idx_samples_task_id', $existingIndexes)) {
                $table->index('task_id', 'idx_samples_task_id');
            }
            if (!in_array('idx_samples_location_id', $existingIndexes)) {
                $table->index('location_id', 'idx_samples_location_id');
            }
        });

        // Step 3: Recreate the foreign keys safely
        Schema::table('samples', function (Blueprint $table) {
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('location_id', 'samples_new_location_fk')->references('id')->on('locations')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingFks = collect(DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME')->toArray();

        Schema::table('samples', function (Blueprint $table) use ($existingFks) {
            if (in_array('samples_new_task_fk', $existingFks)) {
                $table->dropForeign('samples_new_task_fk');
            }
            if (in_array('samples_new_location_fk', $existingFks)) {
                $table->dropForeign('samples_new_location_fk');
            }

            $table->dropIndex('idx_samples_location_id');

            $table->index(['task_id', 'status'], 'idx_samples_task_status');
            $table->index(['location_id', 'status'], 'idx_samples_location_status');
            
            // Re-add moved indexes
            $table->index('task_id', 'samples_task_deleted_idx');
            $table->index('created_at', 'idx_samples_created_at');

            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('location_id', 'samples_new_location_fk')->references('id')->on('locations')->onDelete('restrict');
        });
    }
};
