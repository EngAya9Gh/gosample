<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تحسينات إضافية على جدول car_tracking لتقليل الحجم (~144 MB توفيراً).
 * الـ Migration آمن للتكرار (Idempotent): يتحقق من الوضع الحالي قبل كل تعديل.
 *
 * التوفير المتوقع بعد OPTIMIZE TABLE:
 * - task_id  BIGINT(8) → INT UNSIGNED(4)     (max=757,610)  → ~24 MB
 * - car_id   BIGINT(8) → SMALLINT UNSIGNED(2) (max=250)     → ~36 MB
 * - afaqi_updated_at VARCHAR → DATETIME(8)                   → ~60 MB
 * - حذف updated_at (4 بايت × 6M)                            → ~24 MB
 * - فهرس مركب (task_id, created_at) بدلاً من فهرس منفرد    → تسريع الاستعلامات
 */
return new class extends Migration
{
    public function up()
    {
        // ① تصغير task_id إذا كان لا يزال BIGINT
        $taskIdCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'task_id'");
        if ($taskIdCol && str_contains(strtolower($taskIdCol[0]->Type), 'bigint')) {
            DB::statement("ALTER TABLE car_tracking MODIFY task_id INT UNSIGNED NULL");
        }

        // ② تصغير car_id إذا كان لا يزال BIGINT
        $carIdCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'car_id'");
        if ($carIdCol && str_contains(strtolower($carIdCol[0]->Type), 'bigint')) {
            DB::statement("ALTER TABLE car_tracking MODIFY car_id SMALLINT UNSIGNED NULL");
        }

        // ③ تحويل afaqi_updated_at إذا كان لا يزال VARCHAR
        $afaqiCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'afaqi_updated_at'");
        if ($afaqiCol && str_contains(strtolower($afaqiCol[0]->Type), 'varchar')) {
            DB::statement("ALTER TABLE car_tracking MODIFY afaqi_updated_at DATETIME NULL");
        }

        // ④ حذف updated_at إذا كان لا يزال موجوداً
        if (Schema::hasColumn('car_tracking', 'updated_at')) {
            DB::statement("ALTER TABLE car_tracking DROP COLUMN updated_at");
        }

        // ⑤ الفهرس المركب (task_id, created_at) تم إنشاؤه في migration السابق مباشرة.
        //    لا حاجة لأي تعديل هنا.
    }

    public function down()
    {
        // عكس task_id
        $taskIdCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'task_id'");
        if ($taskIdCol && !str_contains(strtolower($taskIdCol[0]->Type), 'bigint')) {
            DB::statement("ALTER TABLE car_tracking MODIFY task_id BIGINT NULL");
        }

        // عكس car_id
        $carIdCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'car_id'");
        if ($carIdCol && !str_contains(strtolower($carIdCol[0]->Type), 'bigint')) {
            DB::statement("ALTER TABLE car_tracking MODIFY car_id BIGINT NULL");
        }

        // عكس afaqi_updated_at
        $afaqiCol = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'afaqi_updated_at'");
        if ($afaqiCol && !str_contains(strtolower($afaqiCol[0]->Type), 'varchar')) {
            DB::statement("ALTER TABLE car_tracking MODIFY afaqi_updated_at VARCHAR(255) NULL");
        }

        // إعادة updated_at
        if (!Schema::hasColumn('car_tracking', 'updated_at')) {
            DB::statement("ALTER TABLE car_tracking ADD COLUMN updated_at TIMESTAMP NULL");
        }

        // الفهرس يُدار بواسطة migration السابق.
    }
};
