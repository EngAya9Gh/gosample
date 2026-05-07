<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // Seed some defaults
        DB::table('shift_templates')->insert([
            ['name' => 'الشيفت الصباحي (Morning)', 'start_time' => '08:00:00', 'end_time' => '16:00:00'],
            ['name' => 'الشيفت المسائي (Evening)', 'start_time' => '16:00:00', 'end_time' => '00:00:00'],
            ['name' => 'الشيفت الليلي (Night)', 'start_time' => '00:00:00', 'end_time' => '08:00:00'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('shift_templates');
    }
};
