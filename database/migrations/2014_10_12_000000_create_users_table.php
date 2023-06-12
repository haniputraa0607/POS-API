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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('equal_id')->unique();
            $table->string('name');
            $table->string('username');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('idc')->comment('nik ktp')->unique();
            $table->date('birthdate');
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('type', config('user_type'))->default('cashier');
            $table->integer('outlet_id');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
