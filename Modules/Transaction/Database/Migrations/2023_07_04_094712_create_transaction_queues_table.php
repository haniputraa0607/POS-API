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
        Schema::create('transaction_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_transaction');
            $table->string('consultation_queue');
            $table->string('product_queue');
            $table->string('treatment_queue');
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
        Schema::dropIfExists('transaction_queues');
    }
};
