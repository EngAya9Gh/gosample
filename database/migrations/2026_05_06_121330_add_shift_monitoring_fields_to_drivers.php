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
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'shift_count')) {
                $table->integer('shift_count')->default(1)->after('working_hours_end');
            }
            if (!Schema::hasColumn('drivers', 'second_shift_working_hours_start')) {
                $table->time('second_shift_working_hours_start')->nullable()->after('shift_count');
            }
            if (!Schema::hasColumn('drivers', 'second_shift_working_hours_end')) {
                $table->time('second_shift_working_hours_end')->nullable()->after('second_shift_working_hours_start');
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
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['shift_count', 'second_shift_working_hours_start', 'second_shift_working_hours_end']);
        });
    }
};
