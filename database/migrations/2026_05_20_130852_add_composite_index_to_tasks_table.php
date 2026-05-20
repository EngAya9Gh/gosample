<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('tasks');

        // فهرس مركّب (driver_id, billing_client) لتسريع:
        // 1. استعلام top_drivers (GROUP BY driver_id)
        // 2. استعلام عدد المهام لكل عميل (WHERE billing_client = ?)
        $hasComposite = false;
        foreach ($indexes as $index) {
            $cols = $index->getColumns();
            if (in_array('driver_id', $cols) && in_array('billing_client', $cols)) {
                $hasComposite = true;
                break;
            }
        }

        if (!$hasComposite) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['driver_id', 'billing_client'], 'tasks_driver_billing_idx');
            });
        }

        // فهرس على billing_client وحده (لاستعلامات WHERE billing_client = ?)
        $hasBillingIndex = false;
        foreach ($indexes as $index) {
            if ($index->getColumns() === ['billing_client']) {
                $hasBillingIndex = true;
                break;
            }
        }

        if (!$hasBillingIndex) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index('billing_client', 'tasks_billing_client_idx');
            });
        }
    }

    public function down()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('tasks');

        if (array_key_exists('tasks_driver_billing_idx', $indexes)) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_driver_billing_idx');
            });
        }

        if (array_key_exists('tasks_billing_client_idx', $indexes)) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_billing_client_idx');
            });
        }
    }
};
