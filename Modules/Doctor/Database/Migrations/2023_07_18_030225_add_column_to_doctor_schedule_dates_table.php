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
        Schema::table('doctor_schedule_dates', function (Blueprint $table) {
            $table->dropForeign('doctor_schedule_dates_doctor_shift_id_foreign');
            $table->dropColumn('doctor_shift_id');
            $table->date('date')->after('doctor_schedule_id');
        });

        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->integer('quota')->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctor_schedule_dates', function (Blueprint $table) {
            $table->foreignId('doctor_shift_id')->constrained('doctor_shifts')->after('doctor_schedule_id');
            $table->dropColumn('date');
        });

        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->dropColumn('quota');
        });
    }
};
