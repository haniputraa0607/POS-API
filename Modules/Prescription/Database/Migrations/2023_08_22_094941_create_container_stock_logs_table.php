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
        Schema::create('container_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_stock_id')->constrained('container_stocks');
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
        Schema::dropIfExists('container_stock_logs');
    }
};
