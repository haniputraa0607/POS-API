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
        Schema::create('user_has_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('doctor_shift_id')->constrained('doctor_shifts');
            $table->timestamps();
        });
        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->dropForeign('doctor_shifts_user_id_foreign');
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_has_shift');
        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->after('price', function(Blueprint $table){
                $table->foreignId('user_id')->constrained('users');
            });
        });
    }
};
