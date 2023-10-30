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
        Schema::table('doctor_suggestion_products', function (Blueprint $table) {
            $table->after('queue_code', function(Blueprint $table){
                $table->boolean('not_purchase')->default(0);
            });
        });

        Schema::table('doctor_suggestion_prescriptions', function (Blueprint $table) {
            $table->after('queue_code', function(Blueprint $table){
                $table->boolean('not_purchase')->default(0);
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
        Schema::table('doctor_suggestion_products', function (Blueprint $table) {
            $table->dropColumn('not_purchase');
        });

        Schema::table('doctor_suggestion_prescriptions', function (Blueprint $table) {
            $table->dropColumn('not_purchase');
        });
    }
};
