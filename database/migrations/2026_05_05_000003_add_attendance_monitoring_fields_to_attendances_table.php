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
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('driver_id');
            $table->time('expected_start')->nullable()->after('checkout_time');
            $table->time('expected_end')->nullable()->after('expected_start');
            $table->integer('delay_minutes')->default(0)->after('expected_end');
            $table->boolean('is_late')->default(false)->after('delay_minutes');
            $table->integer('early_leave_minutes')->default(0)->after('is_late');
            $table->integer('overtime_minutes')->default(0)->after('early_leave_minutes');
            $table->integer('total_worked_minutes')->default(0)->after('overtime_minutes');
            $table->boolean('alert_sent')->default(false)->after('total_worked_minutes');
            $table->string('source')->nullable()->default('manual')->after('alert_sent')->comment('manual, auto, app');
            
            $table->foreign('shift_id')->references('id')->on('driver_shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn([
                'shift_id',
                'expected_start',
                'expected_end',
                'delay_minutes',
                'is_late',
                'early_leave_minutes',
                'overtime_minutes',
                'total_worked_minutes',
                'alert_sent',
                'source'
            ]);
        });
    }
};
