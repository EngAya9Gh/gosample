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
        $existingIndexes = collect(DB::select('SHOW INDEX FROM tasks'))->pluck('Key_name')->unique()->toArray();

        Schema::table('tasks', function (Blueprint $table) use ($existingIndexes) {
            // Drop the redundant index because (driver_id, collection_date) and (driver_id, billing_client) already cover driver_id lookups
            if (in_array('tasks_new_driver_id_index', $existingIndexes)) {
                $table->dropIndex('tasks_new_driver_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('driver_id', 'tasks_new_driver_id_index');
        });
    }
};
