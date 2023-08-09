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
        Schema::create('partner_stores', function (Blueprint $table) {
            $table->id();
            $table->integer('equal_id');
            $table->foreignId('partner_equal_id')->nullable(true)->constrained('partner_equals');
            $table->string('store_name');
            $table->string('store_address');
            $table->string('store_city');
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
        Schema::dropIfExists('partner_stores');
    }
};
