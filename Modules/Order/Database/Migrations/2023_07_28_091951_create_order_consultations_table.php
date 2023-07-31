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
        Schema::create('order_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('doctor_id')->constrained('users');
            $table->date('schedule_date')->nullable();
            $table->foreignId('doctor_shift_id')->constrained('doctor_shifts');
            $table->integer('order_consultation_price')->default(0);
            $table->integer('order_consultation_subtotal')->default(0);
            $table->integer('order_consultation_discount')->default(0);
            $table->decimal('order_consultation_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_consultation_grandtotal')->default(0);
            $table->integer('queue')->nullable();
            $table->string('queue_code')->nullable();
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
        Schema::dropIfExists('order_consultations');
    }
};
