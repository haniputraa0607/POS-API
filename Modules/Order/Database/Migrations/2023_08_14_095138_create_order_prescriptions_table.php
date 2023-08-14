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
        Schema::create('order_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('prescription_id')->constrained('prescriptions');
            $table->integer('qty')->default(1);
            $table->integer('order_prescription_price')->default(0);
            $table->integer('order_prescription_subtotal')->default(0);
            $table->integer('order_prescription_discount')->default(0);
            $table->decimal('order_prescription_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_prescription_grandtotal')->default(0);
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
        Schema::dropIfExists('order_prescriptions');
    }
};
