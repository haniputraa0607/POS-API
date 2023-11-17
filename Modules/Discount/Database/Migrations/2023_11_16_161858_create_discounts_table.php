<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('equal_id')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'product',
                'consultation',
                'treatment',
                'minimum_transaction',
            ])->nullable();
            $table->float('mimimum_transaction_precentage')->nullable();
            $table->integer('mimimum_transaction_amount')->nullable();
            $table->float('dicount_precentage')->nullable();
            $table->integer('discount_amount')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discounts');
    }
};
