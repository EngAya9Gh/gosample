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
        Schema::table('samples', function (Blueprint $table) {
            // Drop foreign keys first to unlock the composite indexes
            $table->dropForeign('samples_new_task_fk');
            $table->dropForeign('samples_new_location_fk');

            // Drop the massive redundant composite indexes!
            $table->dropIndex('idx_samples_task_status');
            $table->dropIndex('idx_samples_location_status');

            // We KEEP the 'status' column to ensure the Admin Panel and API do not crash.
            // $table->dropColumn('status'); <-- Canceled for safety!

            // Re-create lean standalone indexes (The user astutely noted that we need a pure task_id index!)
            $table->index('task_id', 'idx_samples_task_id');
            $table->index('location_id', 'idx_samples_location_id');

            // Recreate the foreign keys
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('location_id', 'samples_new_location_fk')->references('id')->on('locations')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropForeign('samples_new_task_fk');
            $table->dropForeign('samples_new_location_fk');

            $table->dropIndex('idx_samples_location_id');

            $table->index(['task_id', 'status'], 'idx_samples_task_status');
            $table->index(['location_id', 'status'], 'idx_samples_location_status');

            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('location_id', 'samples_new_location_fk')->references('id')->on('locations')->onDelete('restrict');
        });
    }
};
