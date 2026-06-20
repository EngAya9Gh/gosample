<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add confirmation_method to samples
        Schema::table('samples', function (Blueprint $table) {
            $table->string('confirmation_method')->nullable()->after('confirmed_by');
        });

        // Modify confirmed_by_client enum in tasks
        DB::statement("ALTER TABLE tasks MODIFY COLUMN confirmed_by_client ENUM('YES', 'NO', 'PARTIAL') DEFAULT 'NO'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn('confirmation_method');
        });

        // Revert enum back (Warning: data might be lost if there are 'PARTIAL' records)
        DB::statement("ALTER TABLE tasks MODIFY COLUMN confirmed_by_client ENUM('YES', 'NO') DEFAULT 'NO'");
    }
};
