<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('notifications');

        // فحص: هل يوجد فهرس على read_at مسبقاً؟
        $hasReadAtIndex = false;
        foreach ($indexes as $index) {
            if (in_array('read_at', $index->getColumns())) {
                $hasReadAtIndex = true;
                break;
            }
        }

        if (!$hasReadAtIndex) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index('read_at', 'notifications_read_at_idx');
            });
        }
    }

    public function down()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        if (array_key_exists('notifications_read_at_idx', $sm->listTableIndexes('notifications'))) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_read_at_idx');
            });
        }
    }
};
