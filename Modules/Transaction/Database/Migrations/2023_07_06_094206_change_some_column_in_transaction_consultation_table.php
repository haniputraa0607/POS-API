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
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->dropColumn('id_transaction');
            $table->dropColumn('id_doctor');
            $table->foreignId('transaction_id')->constrained('transactions')->after('id');
            $table->foreignId('user_id')->constrained('users')->after('transaction_id');
            $table->foreignId('doctor_schedule_date_id')->constrained('doctor_schedule_dates')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
            $table->dropColumn('user_id');
            $table->dropColumn('doctor_schedule_date_id');
            $table->unsignedInteger('id_transaction')->after('id');
            $table->unsignedInteger('id_doctor')->after('id_transaction');
        });
    }
};
