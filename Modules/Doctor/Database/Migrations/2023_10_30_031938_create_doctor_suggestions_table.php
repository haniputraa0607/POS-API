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
        Schema::create('doctor_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId('order_id')->constrained('orders');
            $table->dateTime('suggestion_date');
            $table->integer('order_subtotal')->default(0);
            $table->integer('order_gross')->default(0);
            $table->integer('order_discount')->default(0);
            $table->decimal('order_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_grandtotal')->default(0);
            $table->boolean('send_to_transaction')->default(false);
            $table->dateTime('send_to_transaction_date')->nullable();
            $table->dateTime('cancel_date')->nullable();
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
        Schema::dropIfExists('doctor_suggestions');
    }
};
