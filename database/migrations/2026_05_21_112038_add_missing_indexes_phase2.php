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
        Schema::table('sample_tracking', function (Blueprint $table) {
            $table->index('task_id', 'sample_tracking_task_id_idx');
        });

        Schema::table('car_tracking', function (Blueprint $table) {
            $table->index(['car_id', 'created_at'], 'car_tracking_car_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_type_id_created_idx');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->index('parent_id', 'scheduled_tasks_parent_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sample_tracking', function (Blueprint $table) {
            $table->dropIndex('sample_tracking_task_id_idx');
        });

        Schema::table('car_tracking', function (Blueprint $table) {
            $table->dropIndex('car_tracking_car_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_type_id_created_idx');
        });

        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->dropIndex('scheduled_tasks_parent_id_idx');
        });
    }
};
