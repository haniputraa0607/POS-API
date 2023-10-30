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
        Schema::create('doctor_suggestion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_suggestion_id')->constrained('doctor_suggestions');
            $table->foreignId('order_product_id')->nullable()->constrained('order_products');
            $table->foreignId('product_id')->constrained('products');
            $table->enum('type', config('product_type'))->default('Product');
            $table->integer('qty')->default(1);
            $table->date('schedule_date')->nullable();
            $table->integer('step')->nullable();
            $table->integer('total_step')->nullable();
            $table->integer('order_product_price')->default(0);
            $table->integer('order_product_subtotal')->default(0);
            $table->integer('order_product_discount')->default(0);
            $table->decimal('order_product_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_product_grandtotal')->default(0);
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
        Schema::dropIfExists('doctor_suggestion_products');
    }
};
