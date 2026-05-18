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
            // Safe guard: check existing indexes before creating them
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('tasks');

            if (!array_key_exists('tasks_deleted_at_index', $indexes)) {
                $table->index('deleted_at');
            }
            if (!array_key_exists('tasks_deleted_at_created_at_index', $indexes)) {
                $table->index(['deleted_at', 'created_at']);
            }
            if (!array_key_exists('tasks_deleted_at_collection_date_index', $indexes)) {
                $table->index(['deleted_at', 'collection_date']);
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
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['deleted_at', 'created_at']);
            $table->dropIndex(['deleted_at', 'collection_date']);
        });
    }
};
