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

        $existingFks = collect(DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME')->toArray();

        // 2. Drop existing RESTRICT foreign keys and add CASCADE foreign keys
        Schema::table('elm_notifications', function (Blueprint $table) use ($existingFks) {
            if (in_array('elm_notifications_ibfk_1', $existingFks)) {
                $table->dropForeign('elm_notifications_ibfk_1');
            }
            // Add it if it doesn't exist to ensure consistency
            $table->foreign('task_id', 'elm_notifications_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('swap_requests', function (Blueprint $table) use ($existingFks) {
            if (in_array('swap_requests_ibfk_1', $existingFks)) {
                $table->dropForeign('swap_requests_ibfk_1');
            }
            $table->foreign('task_id', 'swap_requests_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('notifications', function (Blueprint $table) use ($existingFks) {
            if (in_array('notifications_ibfk_1', $existingFks)) {
                $table->dropForeign('notifications_ibfk_1');
            }
            $table->foreign('task_id', 'notifications_ibfk_1')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::table('samples', function (Blueprint $table) use ($existingFks) {
            if (in_array('samples_new_task_fk', $existingFks)) {
                $table->dropForeign('samples_new_task_fk');
            }
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingFks = collect(DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME')->toArray();

        Schema::table('elm_notifications', function (Blueprint $table) use ($existingFks) {
            if (in_array('elm_notifications_ibfk_1', $existingFks)) {
                $table->dropForeign('elm_notifications_ibfk_1');
            }
            $table->foreign('task_id', 'elm_notifications_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('swap_requests', function (Blueprint $table) use ($existingFks) {
            if (in_array('swap_requests_ibfk_1', $existingFks)) {
                $table->dropForeign('swap_requests_ibfk_1');
            }
            $table->foreign('task_id', 'swap_requests_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('notifications', function (Blueprint $table) use ($existingFks) {
            if (in_array('notifications_ibfk_1', $existingFks)) {
                $table->dropForeign('notifications_ibfk_1');
            }
            $table->foreign('task_id', 'notifications_ibfk_1')->references('id')->on('tasks')->onDelete('restrict');
        });

        Schema::table('samples', function (Blueprint $table) use ($existingFks) {
            if (in_array('samples_new_task_fk', $existingFks)) {
                $table->dropForeign('samples_new_task_fk');
            }
            $table->foreign('task_id', 'samples_new_task_fk')->references('id')->on('tasks')->onDelete('restrict');
        });
    }
};
