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
            $table->after('status', function (Blueprint $table) {
                $table->dateTime('start_time')->nullable();
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
            $table->dropColumn('start_time');
        });
    }
};
