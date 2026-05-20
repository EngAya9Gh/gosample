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
        $sm = Schema::getConnection()->getDoctrineSchemaManager();

        // 1. Index on notifications.created_at
        $notificationIndexes = $sm->listTableIndexes('notifications');
        $hasNotificationIndex = false;
        foreach ($notificationIndexes as $index) {
            if (in_array('created_at', $index->getColumns())) {
                $hasNotificationIndex = true;
                break;
            }
        }
        if (!$hasNotificationIndex) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('created_at', 'notifications_created_at_idx');
            });
        }

        // 2. Index on tasks.driver_id
        $taskIndexes = $sm->listTableIndexes('tasks');
        $hasTaskIndex = false;
        foreach ($taskIndexes as $index) {
            if (in_array('driver_id', $index->getColumns())) {
                $hasTaskIndex = true;
                break;
            }
        }
        if (!$hasTaskIndex) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index('driver_id', 'tasks_driver_id_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            if (array_key_exists('notifications_created_at_idx', $sm->listTableIndexes('notifications'))) {
                $table->dropIndex('notifications_created_at_idx');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            if (array_key_exists('tasks_driver_id_idx', $sm->listTableIndexes('tasks'))) {
                $table->dropIndex('tasks_driver_id_idx');
            }
        });
    }
};
