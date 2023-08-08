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
            $table->after('start_date', function(Blueprint $table){
                $table->integer('timeframe')->nullable();
                $table->enum('timeframe_type', ['Day', 'Week', 'Month', 'Year'])->default('Day');
            });
            $table->string('suggestion')->nullable()->change();
        });

        Schema::table('treatment_patient_steps', function (Blueprint $table) {
            $table->after('date', function(Blueprint $table){
                $table->enum('status', ['Pending', 'Finished'])->default('Pending');
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
            $table->dropColumn('timeframe');
            $table->dropColumn('timeframe_type');
            $table->string('suggestion')->nullable(false)->change();

        });

        Schema::table('treatment_patient_steps', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
