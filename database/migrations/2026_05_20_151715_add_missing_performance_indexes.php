<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sample_tracking')) {
            Schema::table('sample_tracking', function (Blueprint $table) {
                $table->index('sample_id', 'sample_tracking_sample_id_idx');
            });
        }

        Schema::table('samples', function (Blueprint $table) {
            $table->index(['deleted_at', 'temperature_type'], 'samples_deleted_temperature_idx');
            $table->index(['task_id', 'deleted_at'], 'samples_task_deleted_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['status', 'deleted_at', 'created_at'], 'tasks_status_del_created_idx');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->index(['status', 'day', 'deleted_at'], 'sched_tasks_status_day_del_idx');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->index(['driver_id', 'status', 'deleted_at'], 'cars_driver_status_del_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('sample_tracking')) {
            Schema::table('sample_tracking', function (Blueprint $table) {
                $table->dropIndex('sample_tracking_sample_id_idx');
            });
        }

        Schema::table('samples', function (Blueprint $table) {
            $table->dropIndex('samples_deleted_temperature_idx');
            $table->dropIndex('samples_task_deleted_idx');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_del_created_idx');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->dropIndex('sched_tasks_status_day_del_idx');
        });

        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex('cars_driver_status_del_idx');
        });
    }
};
