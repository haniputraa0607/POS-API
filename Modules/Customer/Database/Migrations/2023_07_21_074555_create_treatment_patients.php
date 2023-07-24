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
        Schema::create('treatment_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained('products');
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId('doctor_id')->nullable()->constrained('users');
            $table->integer('step')->default(1);
            $table->integer('progress')->default(0);
            $table->enum('status', ['Pending', 'On Progress', 'Finished'])->default('Pending');
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
        Schema::dropIfExists('treatment_patients');
    }
};
