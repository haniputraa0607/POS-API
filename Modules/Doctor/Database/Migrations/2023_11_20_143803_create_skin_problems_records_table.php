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
        Schema::create('skin_problem_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId('order_id')->constrained('orders');
            $table->string('name');
            $table->text('time_period');
            $table->enum('tried_solution', ['yes', 'no']);
            $table->text('solution')->nullable();
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
        Schema::dropIfExists('skin_problem_records');
    }
};
