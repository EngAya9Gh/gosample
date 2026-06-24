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
            // Drop duplicate index that was created when deleted_at was dropped from the composite index
            $table->dropIndex('tasks_deleted_at_driver_id_idx');
            
            // Drop other orphaned indexes that lost their primary purpose
            $table->dropIndex('tasks_deleted_at_created_at_index');
            $table->dropIndex('tasks_deleted_at_collection_date_index');
            $table->dropIndex('tasks_status_del_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('driver_id', 'tasks_deleted_at_driver_id_idx');
            $table->index('created_at', 'tasks_deleted_at_created_at_index');
            $table->index('collection_date', 'tasks_deleted_at_collection_date_index');
            $table->index(['status', 'created_at'], 'tasks_status_del_created_idx');
        });
    }
};
