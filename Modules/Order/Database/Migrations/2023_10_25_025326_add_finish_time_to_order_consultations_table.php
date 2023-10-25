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
        Schema::table('order_consultations', function (Blueprint $table) {
            $table->after('start_time', function (Blueprint $table) {
                $table->dateTime('finish_time')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_consultations', function (Blueprint $table) {
            $table->dropColumn('finish_time');
        });
    }
};
