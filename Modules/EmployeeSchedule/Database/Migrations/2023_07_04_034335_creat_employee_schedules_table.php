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
        Schema::create('employee_shcedules', function(Blueprint $table)
		{
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
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
        Schema::dropIfExists('employee_shcedules');
    }
};
