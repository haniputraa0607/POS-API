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
        Schema::create('transaction_consultations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_transaction');
            $table->unsignedInteger('id_doctor');
            $table->date('schdule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('treament_recommendations');
            $table->integer('transaction_consultation_subtotal');
            $table->integer('transaction_consultation_tax');
            $table->integer('transaction_consultation_gross');
            $table->integer('transaction_consultation_discount');
            $table->integer('transaction_consultation_price');
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
        Schema::dropIfExists('transaction_consultations');
    }
};
