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
        Schema::rename('employee_shcedules', 'employee_schedules');

        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropColumn('date');
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
            $table->foreignId('outlet_id')->constrained('outlets')->after('user_id');
            $table->integer('schedule_month')->after('outlet_id');
            $table->integer('schedule_year')->after('schedule_month');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('employee_schedules', 'employee_shcedules');

        Schema::table('employee_shcedules', function (Blueprint $table) {
            $table->dropColumn('outlet_id');
            $table->dropColumn('schedule_month');
            $table->dropColumn('schedule_year');
            $table->date('date')->after('user_id');
            $table->time('start_time')->after('date');
            $table->time('end_time')->after('start_time');
        });
    }
};
