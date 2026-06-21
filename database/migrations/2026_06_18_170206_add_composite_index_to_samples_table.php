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
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->index(['created_at', 'temperature_type'], 'samples_dashboard_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropIndex('samples_dashboard_index');
        });
    }
};
