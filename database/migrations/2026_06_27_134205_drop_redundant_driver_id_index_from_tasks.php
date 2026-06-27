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
            // Drop the redundant index because (driver_id, collection_date) and (driver_id, billing_client) already cover driver_id lookups
            if (Schema::hasColumn('tasks', 'driver_id')) {
                // Ensure we only drop if we are certain it exists to prevent errors on some setups
                try {
                    $table->dropIndex('tasks_new_driver_id_index');
                } catch (\Exception $e) {
                    // Ignore if it's already dropped
                }
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
