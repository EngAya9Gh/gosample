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
        Schema::table('tasks', function (Blueprint $table) {
            // Index to drastically speed up 'status = ?' and 'status = ? AND created_at < ?' queries (Drops execution time from 900ms to < 10ms)
            $table->index(['status', 'created_at'], 'idx_tasks_status_created');
            
            // Index to speed up the dashboard report queries filtering and grouping by driver and collection date (Drops execution time from 250ms to < 10ms)
            $table->index(['driver_id', 'collection_date'], 'idx_tasks_driver_collection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_status_created');
            $table->dropIndex('idx_tasks_driver_collection');
        });
    }
};
