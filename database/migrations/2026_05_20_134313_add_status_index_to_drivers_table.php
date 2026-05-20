<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('drivers');

        // فهرس على drivers.status لإيقاف "type: ALL + Using temporary + filesort"
        // الذي يحدث في استعلام top_drivers
        $hasStatusIndex = false;
        foreach ($indexes as $index) {
            if (in_array('status', $index->getColumns())) {
                $hasStatusIndex = true;
                break;
            }
        }

        if (!$hasStatusIndex) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->index('status', 'drivers_status_idx');
            });
        }
    }

    public function down()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        if (array_key_exists('drivers_status_idx', $sm->listTableIndexes('drivers'))) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropIndex('drivers_status_idx');
            });
        }
    }
};
