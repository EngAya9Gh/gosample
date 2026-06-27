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
        // 1. Clean up any orphaned rows that would prevent adding the new foreign keys on production
        DB::table('notifications')->whereNotExists(function($q) { $q->select(DB::raw(1))->from('tasks')->whereColumn('tasks.id', 'notifications.task_id'); })->whereNotNull('task_id')->delete();
        DB::table('swap_requests')->whereNotExists(function($q) { $q->select(DB::raw(1))->from('tasks')->whereColumn('tasks.id', 'swap_requests.task_id'); })->whereNotNull('task_id')->delete();
        DB::table('elm_notifications')->whereNotExists(function($q) { $q->select(DB::raw(1))->from('tasks')->whereColumn('tasks.id', 'elm_notifications.task_id'); })->whereNotNull('task_id')->delete();
        DB::table('samples')->whereNotExists(function($q) { $q->select(DB::raw(1))->from('tasks')->whereColumn('tasks.id', 'samples.task_id'); })->whereNotNull('task_id')->delete();

        // 2. Drop existing RESTRICT foreign keys and add CASCADE foreign keys
        Schema::table('elm_notifications', function (Blueprint $table) {
            // We use try/catch or just drop it if it exists. 
            // In a clean production environment, these exist and must be dropped first.
            $table->dropForeign('elm_notifications_ibfk_1');
            $table->foreign('task_id', 'elm_notifications_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('swap_requests', function (Blueprint $table) {
            $table->dropForeign('swap_requests_ibfk_1');
            $table->foreign('task_id', 'swap_requests_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign('notifications_ibfk_1');
            $table->foreign('task_id', 'notifications_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

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
