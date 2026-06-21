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
            // Drop the deleted_at column completely
            $table->dropSoftDeletes();
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
            // Restore the deleted_at column if we ever need to rollback
            $table->softDeletes();
        });
    }
};
