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
        Schema::table('treatment_patients', function (Blueprint $table) {
            $table->after('status', function (Blueprint $table) {
                $table->date('start_date')->nullable();
                $table->date('expired_date')->nullable();
                $table->string('suggestion');
            });
        });

        Schema::table('treatment_patient_steps', function (Blueprint $table) {
            $table->after('step', function (Blueprint $table) {
                $table->dateTime('date')->nullable();
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
        Schema::table('treatment_patients', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('expired_date');
            $table->dropColumn('suggestion');
        });

        Schema::table('treatment_patient_steps', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
