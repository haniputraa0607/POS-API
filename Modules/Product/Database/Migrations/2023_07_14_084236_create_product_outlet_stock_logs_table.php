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
        Schema::create('product_outlet_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_outlet_stock_id')->constrained('product_outlet_stocks');
            $table->integer('qty');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('source');
            $table->string('description')->nullable();
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
        Schema::dropIfExists('product_outlet_stock_logs');
    }
};
