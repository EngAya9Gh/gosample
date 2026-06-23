<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * يوثق التعديلات التي تم تنفيذها يدوياً على قاعدة البيانات.
 * الـ Migration آمن للتكرار (Idempotent): يتحقق من نوع العمود الحالي قبل أي تعديل.
 *
 * التعديلات:
 * - تنظيف القيم الفارغة في حقول درجات الحرارة والإحداثيات
 * - تحويل temp5/6/7/8 من VARCHAR(255) إلى DECIMAL(8,2)
 * - تحويل lat/lng من VARCHAR(255) إلى DECIMAL(10,7)
 */
return new class extends Migration
{
    public function up()
    {
        // التحقق من نوع عمود temp5: إذا كان لا يزال varchar نقوم بالتحويل
        $col = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'temp5'");
        if ($col && str_contains(strtolower($col[0]->Type), 'varchar')) {

            // الخطوة 1: تنظيف القيم الفارغة
            DB::statement("
                UPDATE car_tracking
                SET temp5 = IF(temp5 = '', NULL, temp5),
                    temp6 = IF(temp6 = '', NULL, temp6),
                    temp7 = IF(temp7 = '', NULL, temp7),
                    temp8 = IF(temp8 = '', NULL, temp8),
                    lat   = IF(lat = '', NULL, lat),
                    lng   = IF(lng = '', NULL, lng)
                WHERE temp5 = '' OR temp6 = '' OR temp7 = '' OR temp8 = '' OR lat = '' OR lng = ''
            ");

            // الخطوة 2: تحويل الأنواع
            DB::statement("
                ALTER TABLE car_tracking
                MODIFY temp5 DECIMAL(8,2) NULL,
                MODIFY temp6 DECIMAL(8,2) NULL,
                MODIFY temp7 DECIMAL(8,2) NULL,
                MODIFY temp8 DECIMAL(8,2) NULL,
                MODIFY lat   DECIMAL(10,7) NULL,
                MODIFY lng   DECIMAL(10,7) NULL
            ");
        }
    }

    public function down()
    {
        $col = DB::select("SHOW COLUMNS FROM car_tracking WHERE Field = 'temp5'");
        if ($col && str_contains(strtolower($col[0]->Type), 'decimal')) {
            DB::statement("
                ALTER TABLE car_tracking
                MODIFY temp5 VARCHAR(255) NULL,
                MODIFY temp6 VARCHAR(255) NULL,
                MODIFY temp7 VARCHAR(255) NULL,
                MODIFY temp8 VARCHAR(255) NULL,
                MODIFY lat   VARCHAR(255) NULL,
                MODIFY lng   VARCHAR(255) NULL
            ");
        }
    }
};
