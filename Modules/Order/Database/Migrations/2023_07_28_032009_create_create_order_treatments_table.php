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
        Schema::table('order_products', function (Blueprint $table) {
            $table->after('type', function (Blueprint $table) {
                $table->date('schedule_date')->nullable();
                $table->foreignId('treatment_patient_id')->nullable()->constrained('treatment_patients');
            });
            $table->after('order_product_grandtotal', function (Blueprint $table) {
                $table->integer('queue')->nullable();
                $table->string('queue_code')->nullable();
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
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('schedule_date');
            $table->dropForeign('order_products_treatment_patient_id_foreign');
            $table->dropColumn('treatment_patient_id');
            $table->dropColumn('queue');
            $table->dropColumn('queue_code');
        });
    }
};
