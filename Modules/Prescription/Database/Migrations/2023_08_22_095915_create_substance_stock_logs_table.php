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
        Schema::create('substance_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_stock_id')->constrained('substance_stocks');
            $table->float('qty');
            $table->float('stock_before');
            $table->float('stock_after');
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
        Schema::dropIfExists('substance_stock_logs');
    }
};
