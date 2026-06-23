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
            $table->index('task_id', 'car_tracking_task_id_idx');
        });
    }

    public function down()
    {
        Schema::table('car_tracking', function (Blueprint $table) {
            $table->dropIndex('car_tracking_task_id_idx');
        });
    }
};
