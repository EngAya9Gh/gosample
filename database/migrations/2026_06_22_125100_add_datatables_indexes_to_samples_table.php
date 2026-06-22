<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إزالة الفهرس المكرر
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            // حذف فهرس temperature_type المكرر (samples_deleted_temperature_idx)
            // لأنه مطابق تماماً لـ idx_temperature_type الذي أنشأناه مؤخراً
            if ($this->indexExists('samples', 'samples_deleted_temperature_idx')) {
                $table->dropIndex('samples_deleted_temperature_idx');
            }
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
            // إعادة الفهرس المحذوف عند التراجع
            if (!$this->indexExists('samples', 'samples_deleted_temperature_idx')) {
                $table->index('temperature_type', 'samples_deleted_temperature_idx');
            }
        });
    }

    /**
     * Check if an index already exists to avoid duplicate index errors
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
