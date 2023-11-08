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
        Schema::create('nurse_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->constrained('nurses');
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->integer('schedule_month');
            $table->integer('schedule_year');
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
        Schema::dropIfExists('nurse_schedules');
    }
};
