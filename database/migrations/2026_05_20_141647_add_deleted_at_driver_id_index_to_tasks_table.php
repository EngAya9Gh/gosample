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
        Schema::table('tasks', function (Blueprint $table) {
            // إضافة فهرس مركب يغطي البحث عن deleted_at مع تجميع driver_id
            $table->index(['deleted_at', 'driver_id'], 'tasks_deleted_at_driver_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_deleted_at_driver_id_idx');
        });
    }
};
