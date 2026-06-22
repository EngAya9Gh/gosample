<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة الفهارس الناقصة فقط بعد مراجعة الفهارس الموجودة مسبقاً
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {

            // 1. فهرس لفلترة العميل (WHERE billing_client IN (...))
            //    غير موجود في الجدول - مطلوب لتسريع DataTables عند تصفية المستخدمين متعددي العملاء
            if (!$this->indexExists('samples', 'idx_samples_billing_client')) {
                $table->index('billing_client', 'idx_samples_billing_client');
            }

            // 2. فهرس مركب: billing_client + created_at
            //    يُسرّع الفلترة بالعميل والتاريخ معاً في نفس الاستعلام
            if (!$this->indexExists('samples', 'idx_samples_client_date')) {
                $table->index(['billing_client', 'created_at'], 'idx_samples_client_date');
            }

            // 3. حذف فهرس temperature_type المكرر (samples_deleted_temperature_idx)
            //    لأنه مطابق تماماً لـ idx_temperature_type الذي أنشأناه مؤخراً
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
            if ($this->indexExists('samples', 'idx_samples_billing_client')) {
                $table->dropIndex('idx_samples_billing_client');
            }
            if ($this->indexExists('samples', 'idx_samples_client_date')) {
                $table->dropIndex('idx_samples_client_date');
            }
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
