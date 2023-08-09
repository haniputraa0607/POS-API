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
        Schema::create('partner_sosial_medias', function (Blueprint $table) {
            $table->id();
            $table->integer('equal_id');
            $table->foreignId('partner_store_id')->nullable(true)->constrained('partner_stores');
            $table->enum('type', config('partner_sosial_type'))->nullable();
            $table->string('url')->nullable();
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
        Schema::dropIfExists('partner_sosial_medias');
    }
};
