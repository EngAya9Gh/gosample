<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إضافة فهارس لتسريع استعلامات DataTables في صفحة العينات
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {

            // 1. فهرس للربط مع جدول tasks (LEFT JOIN tasks ON tasks.id = samples.task_id)
            if (!$this->indexExists('samples', 'idx_samples_task_id')) {
                $table->index('task_id', 'idx_samples_task_id');
            }

            // 2. فهرس لفلترة العميل (WHERE billing_client IN (...))
            if (!$this->indexExists('samples', 'idx_samples_billing_client')) {
                $table->index('billing_client', 'idx_samples_billing_client');
            }

            // 3. فهرس للبحث بالباركود (WHERE barcode_id = ?)
            if (!$this->indexExists('samples', 'idx_samples_barcode_id')) {
                $table->index('barcode_id', 'idx_samples_barcode_id');
            }

            // 4. فهرس لفلترة حالة التأكيد (WHERE confirmed_by_client = ?)
            if (!$this->indexExists('samples', 'idx_samples_confirmed_by_client')) {
                $table->index('confirmed_by_client', 'idx_samples_confirmed_by_client');
            }

            // 5. فهرس مركب للبيانات الأكثر استخداماً معاً: billing_client + created_at
            //    يُسرّع عمليات التصفية والترتيب في نفس الوقت
            if (!$this->indexExists('samples', 'idx_samples_client_date')) {
                $table->index(['billing_client', 'created_at'], 'idx_samples_client_date');
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
            $table->dropIndex('idx_samples_task_id');
            $table->dropIndex('idx_samples_billing_client');
            $table->dropIndex('idx_samples_barcode_id');
            $table->dropIndex('idx_samples_confirmed_by_client');
            $table->dropIndex('idx_samples_client_date');
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
