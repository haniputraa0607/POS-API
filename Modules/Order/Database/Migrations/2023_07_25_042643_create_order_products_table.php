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
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('product_id')->constrained('products');
            $table->enum('type', config('product_type'))->default('Product');
            $table->integer('qty')->default(1);
            $table->integer('order_product_price')->default(0);
            $table->integer('order_product_subtotal')->default(0);
            $table->integer('order_product_discount')->default(0);
            $table->decimal('order_product_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_product_grandtotal')->default(0);
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
        Schema::dropIfExists('order_products');
    }
};
