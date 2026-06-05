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
            $table->integer('route_order')->nullable()->after('poririty');
            $table->integer('cumulative_eta')->nullable()->after('eta');
            $table->timestamp('estimated_arrival_time')->nullable()->after('cumulative_eta');
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
            $table->dropColumn(['route_order', 'cumulative_eta', 'estimated_arrival_time']);
        });
    }
};
