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
        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->dropColumn('quota');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctor_shifts', function (Blueprint $table) {
            $table->after('price', function(Blueprint $table){
                $table->integer('quota');
            });
        });
    }
};
