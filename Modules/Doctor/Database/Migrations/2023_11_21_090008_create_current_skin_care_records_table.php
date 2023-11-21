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
        Schema::create('current_skin_care_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId("order_id")->constrained("orders");
            $table->text('toner')->nullable();
            $table->text('moisturizer')->nullable();
            $table->text('sun_screen')->nullable();
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
        Schema::dropIfExists('current_skin_care_records');
    }
};
