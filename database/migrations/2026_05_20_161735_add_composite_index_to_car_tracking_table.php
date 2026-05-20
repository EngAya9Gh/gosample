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
        Schema::table('car_tracking', function (Blueprint $table) {
            $table->index(['car_id', 'task_id', 'created_at'], 'car_tracking_car_task_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_tracking', function (Blueprint $table) {
            $table->dropIndex('car_tracking_car_task_created_idx');
        });
    }
};
