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
        Schema::table('client_driver', function (Blueprint $table) {
            $table->index(['client_id', 'driver_id', 'deleted_at'], 'client_driver_client_driver_del_idx');
            $table->index(['driver_id', 'client_id', 'deleted_at'], 'client_driver_driver_client_del_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_driver', function (Blueprint $table) {
            $table->dropIndex('client_driver_client_driver_del_idx');
            $table->dropIndex('client_driver_driver_client_del_idx');
        });
    }
};
