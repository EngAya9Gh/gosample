<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * تحسين بنية جدول tasks:
 * 1. تحويل أعمدة الإحداثيات من varchar إلى Decimal لتوفير المساحة وتوحيد النوع.
 * 2. تحويل confirmation_time من varchar إلى Timestamp.
 * 3. حذف العمود الميت driver_start_date3 (لا يستخدم في الكود أبداً).
 * 4. حذف الفهرس المكرر idx_tasks_driver_id الذي يهدر المساحة.
 * 
 * الـ Migration آمن للتكرار (Idempotent).
 */
return new class extends Migration
{
    public function up()
    {
        // 1. تنظيف السلاسل النصية الفارغة وتأمينها لتصبح NULL حتى لا يفشل التحويل
        DB::statement("
            UPDATE tasks 
            SET 
                collect_lat = IF(collect_lat='', NULL, collect_lat),
                collect_lng = IF(collect_lng='', NULL, collect_lng),
                close_lat = IF(close_lat='', NULL, close_lat),
                close_lng = IF(close_lng='', NULL, close_lng),
                confirmation_time = IF(confirmation_time='', NULL, confirmation_time)
            WHERE 
                collect_lat = '' OR collect_lng = '' OR 
                close_lat = '' OR close_lng = '' OR 
                confirmation_time = ''
        ");

        // 2. تحويل أنواع البيانات وحذف العمود الميت
        $cols = DB::select("SHOW COLUMNS FROM tasks WHERE Field = 'collect_lat'");
        if (!empty($cols) && str_contains(strtolower($cols[0]->Type), 'varchar')) {
            DB::statement("
                ALTER TABLE tasks 
                MODIFY collect_lat DECIMAL(10,7) NULL,
                MODIFY collect_lng DECIMAL(10,7) NULL,
                MODIFY close_lat DECIMAL(10,7) NULL,
                MODIFY close_lng DECIMAL(10,7) NULL,
                MODIFY confirmation_time TIMESTAMP NULL
            ");
        }

        // حذف عمود driver_start_date3 الميت
        if (Schema::hasColumn('tasks', 'driver_start_date3')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('driver_start_date3');
            });
        }

        // 3. حذف الفهرس المكرر
        $indexExists = DB::select("SHOW INDEX FROM tasks WHERE Key_name = 'idx_tasks_driver_id'");
        if (!empty($indexExists)) {
            DB::statement("ALTER TABLE tasks DROP INDEX idx_tasks_driver_id");
        }
    }

    public function down()
    {
        $cols = DB::select("SHOW COLUMNS FROM tasks WHERE Field = 'collect_lat'");
        if (!empty($cols) && str_contains(strtolower($cols[0]->Type), 'decimal')) {
            DB::statement("
                ALTER TABLE tasks 
                MODIFY collect_lat VARCHAR(255) NULL,
                MODIFY collect_lng VARCHAR(255) NULL,
                MODIFY close_lat VARCHAR(255) NULL,
                MODIFY close_lng VARCHAR(255) NULL,
                MODIFY confirmation_time VARCHAR(255) NULL
            ");
        }

        if (!Schema::hasColumn('tasks', 'driver_start_date3')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->date('driver_start_date3')->nullable();
            });
        }

        $indexExists = DB::select("SHOW INDEX FROM tasks WHERE Key_name = 'idx_tasks_driver_id'");
        if (empty($indexExists)) {
            DB::statement("ALTER TABLE tasks ADD INDEX idx_tasks_driver_id (driver_id)");
        }
    }
};
