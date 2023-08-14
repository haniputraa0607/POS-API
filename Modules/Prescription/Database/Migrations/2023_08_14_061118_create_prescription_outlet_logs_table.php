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
        Schema::create('prescription_outlet_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_outlet_id')->constrained('prescription_outlets');
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
        Schema::dropIfExists('prescription_outlet_logs');
    }
};
