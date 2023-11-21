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
        Schema::create('skin_type_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId("order_id")->constrained("orders");
            $table->string("skin_type");
            $table->string("skin_tone");
            $table->string("visible_pores_percentage");
            $table->text("visible_pores_description");
            $table->text("wrinkles_description");
            $table->string("skin_texture");
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
        Schema::dropIfExists('skin_type_records');
    }
};
