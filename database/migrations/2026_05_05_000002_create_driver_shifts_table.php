<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('driver_shifts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('driver_id');
            $table->integer('shift_number')->default(1)->comment('e.g., 1, 2, or 3');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('days')->nullable()->comment('Array of days: ["Sunday", "Monday", ...]');
            $table->date('valid_from')->nullable()->comment('When this shift schedule starts');
            $table->date('valid_to')->nullable()->comment('When this shift schedule ends/expired');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_shifts');
    }
};
