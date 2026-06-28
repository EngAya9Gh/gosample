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
        // We only alter car_tracking as requested by the user.
        DB::statement('ALTER TABLE car_tracking MODIFY imei BIGINT UNSIGNED NULL');
        // We do NOT add an index to car_tracking.imei because the application never queries by it (only by car_id).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE car_tracking MODIFY imei VARCHAR(255) NULL');
    }
};
