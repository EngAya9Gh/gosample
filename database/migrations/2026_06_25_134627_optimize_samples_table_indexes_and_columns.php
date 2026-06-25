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
            // Drop redundant indexes to save space and improve insert performance
            $table->dropIndex('samples_task_deleted_idx'); // Duplicate of idx_samples_task_status
            $table->dropIndex('idx_samples_created_at');   // Covered by samples_dashboard_index

         
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
             
            $table->index('task_id', 'samples_task_deleted_idx');
            $table->index('created_at', 'idx_samples_created_at');
        });
    }
};
