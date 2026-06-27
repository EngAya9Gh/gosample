<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // elm_notifications and swap_requests were already successfully updated in the previous partial run.
        
        // notifications (The drop succeeded in the previous run, but add failed. Now we just add it).
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('task_id', 'notifications_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        // samples (Previous run crashed before reaching this)
        Schema::table('samples', function (Blueprint $table) {
            $table->dropForeign('samples_new_task_fk');
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elm_notifications', function (Blueprint $table) {
            $table->dropForeign('elm_notifications_ibfk_1');
            $table->foreign('task_id', 'elm_notifications_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropForeign('swap_requests_ibfk_1');
            $table->foreign('task_id', 'swap_requests_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('notifications_ibfk_1');
            $table->foreign('task_id', 'notifications_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropForeign('samples_new_task_fk');
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('restrict');
        });
    }
};
