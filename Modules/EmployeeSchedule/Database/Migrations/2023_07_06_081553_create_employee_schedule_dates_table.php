<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_schedule_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_schedule_id')->constrained('employee_schedules');
            $table->dateTime('date');
            $table->enum('shift',config('outlet_shift'))->default('Morning');
            $table->time('time_start');
            $table->time('time_end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_schedule_dates');
    }
};
