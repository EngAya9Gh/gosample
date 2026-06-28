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
        // Index drops moved to 2026_06_27_135036_drop_status_column_from_samples_table.php 
        // to avoid Foreign Key constraint errors (Error 1553) during deployment.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kept empty
    }
};
