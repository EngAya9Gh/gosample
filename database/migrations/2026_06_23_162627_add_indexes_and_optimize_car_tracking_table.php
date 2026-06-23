<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * إضافة فهرس مركب (task_id, created_at) على جدول car_tracking.
 *
 * لماذا فهرس مركب وليس منفرداً على task_id فقط؟
 * جميع الاستعلامات على هذا الجدول تستخدم النمط:
 *   WHERE task_id = ? ORDER BY created_at
 * الفهرس المركب يُلغي عملية الترتيب (filesort) في الذاكرة لأن
 * البيانات مرتّبة أصلاً بـ created_at داخل الفهرس.
 * كما أنه يُغني تماماً عن فهرس منفرد على task_id (يعمل كـ prefix).
 *
 * الـ Migration آمن للتكرار (Idempotent).
 */
return new class extends Migration
{
    public function up()
    {
        $exists = DB::select("SHOW INDEX FROM car_tracking WHERE Key_name = 'car_tracking_task_created_idx'");
        if (empty($exists)) {
            DB::statement("ALTER TABLE car_tracking ADD INDEX car_tracking_task_created_idx (task_id, created_at)");
        }
    }

    public function down()
    {
        $exists = DB::select("SHOW INDEX FROM car_tracking WHERE Key_name = 'car_tracking_task_created_idx'");
        if (!empty($exists)) {
            DB::statement("ALTER TABLE car_tracking DROP INDEX car_tracking_task_created_idx");
        }
    }
};
