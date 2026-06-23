<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * حذف الفهرس الثلاثي غير المستخدم (car_id, task_id, created_at).
 * 
 * السبب:
 * تم مراجعة الكود بالكامل ولا يوجد أي استعلام يبحث عن car_id و task_id معاً.
 * هذا الفهرس الثلاثي يستهلك مساحة ضخمة (مئات الميجابايتات) ويزيد من وقت
 * الإدخال (Insert) دون أي فائدة تذكر للنظام.
 * 
 * الـ Migration آمن للتكرار (Idempotent).
 */
return new class extends Migration
{
    public function up()
    {
        // التحقق من وجود الفهرس قبل حذفه
        $exists = DB::select("SHOW INDEX FROM car_tracking WHERE Key_name = 'car_tracking_car_task_created_idx'");
        
        if (!empty($exists)) {
            DB::statement("ALTER TABLE car_tracking DROP INDEX car_tracking_car_task_created_idx");
        }
    }

    public function down()
    {
        // التحقق من عدم وجود الفهرس قبل إعادته
        $exists = DB::select("SHOW INDEX FROM car_tracking WHERE Key_name = 'car_tracking_car_task_created_idx'");
        
        if (empty($exists)) {
            DB::statement("ALTER TABLE car_tracking ADD INDEX car_tracking_car_task_created_idx (car_id, task_id, created_at)");
        }
    }
};
