<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(env('SECOND_DB_DRIVER'))->dropIfExists('log_crons');
        Schema::connection(env('SECOND_DB_DRIVER'))->create('log_crons', function (Blueprint $table) {
            $table->id();
            $table->string('cron')->nullable();
            $table->enum('status',['success','fail','onprocess'])->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(env('SECOND_DB_DRIVER'))->dropIfExists('log_crons');
    }
};
