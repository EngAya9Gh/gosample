<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('employment_type')->nullable()->comment('e.g., full_time, part_time');
            $table->integer('shift_count')->default(1)->comment('Number of shifts per day (1, 2, or 3)');
            $table->integer('total_working_hours')->nullable()->comment('Expected total hours per day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'shift_count', 'total_working_hours']);
        });
    }
};
