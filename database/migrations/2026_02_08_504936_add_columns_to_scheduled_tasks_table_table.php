<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->enum('execution_status', [
                'pending',        // لم يتم فحصه بعد
                'executed',       // تم التنفيذ
                'skipped_late',   // تجاهل بسبب تأخير
                'skipped_locked', // تجاهل بسبب lock
                'skipped_error',   // خطأ أثناء التنفيذ
                'archived'
            ])->default('pending');
        });
        
    }

    public function down(): void
    {
        Schema::table('scheduled_tasks', function (Blueprint $table) {
            $table->dropColumns(['last_checked_at', 'executed_at', 'execution_status']);
        });
    }
};
