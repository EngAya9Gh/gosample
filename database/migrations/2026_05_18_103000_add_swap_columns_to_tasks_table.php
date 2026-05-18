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
            if (!Schema::hasColumn('tasks', 'is_swap')) {
                $table->tinyInteger('is_swap')->default(0)->nullable();
            }
            if (!Schema::hasColumn('tasks', 'old_driver_id')) {
                $table->integer('old_driver_id')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'swap_accepted_date')) {
                $table->dateTime('swap_accepted_date')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'swap_freezer_in')) {
                $table->dateTime('swap_freezer_in')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'swap_freezer_out')) {
                $table->dateTime('swap_freezer_out')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'is_unused')) {
                $table->tinyInteger('is_unused')->default(0)->nullable();
            }
            if (!Schema::hasColumn('tasks', 'is_blazma')) {
                $table->tinyInteger('is_blazma')->default(0)->nullable();
            }
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
            $table->dropColumn([
                'is_swap',
                'old_driver_id',
                'swap_accepted_date',
                'swap_freezer_in',
                'swap_freezer_out',
                'is_unused',
                'is_blazma'
            ]);
        });
    }
};
