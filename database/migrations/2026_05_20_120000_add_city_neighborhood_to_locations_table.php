<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'city')) {
                $table->string('city')->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('locations', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('city');
            }
        });
    }

    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['city', 'neighborhood']);
        });
    }
};
