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
        Schema::table('outlet_schedule_shifts', function (Blueprint $table) {
            $table->after('shift_time_end', function (Blueprint $table) {
                $table->time('start_break')->nullable();
                $table->time('end_break')->nullable();
            });
        });

        Schema::table('employee_schedule_dates', function (Blueprint $table) {
            $table->dropColumn('shift');
            $table->dropColumn('time_start');
            $table->dropColumn('time_end');

            $table->after('date', function (Blueprint $table) {
                $table->foreignId('outlet_schedule_shift_id')->constrained('outlet_schedule_shifts');
            });

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_schedule_shifts', function (Blueprint $table) {
            $table->dropColumn('start_break');
            $table->dropColumn('end_break');
        });

        Schema::table('employee_schedule_dates', function (Blueprint $table) {
            $table->after('date', function (Blueprint $table) {
                $table->enum('shift',config('outlet_shift'))->default('Morning');
                $table->time('time_start')->nullable();
                $table->time('time_end')->nullable();
            });
        });

        Schema::table('employee_schedule_dates', function (Blueprint $table) {
            $table->dropForeign('employee_schedule_dates_outlet_schedule_shift_id_foreign');
            $table->dropColumn('outlet_schedule_shift_id');
        });

    }
};
