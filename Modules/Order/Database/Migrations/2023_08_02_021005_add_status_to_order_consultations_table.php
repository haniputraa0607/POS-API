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
            $table->after('order_consultation_grandtotal', function (Blueprint $table) {
                $table->enum('status', ['Pending', 'Ready', 'On Progress', 'Finished'])->default('Pending');
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
            $table->dropColumn('order_consultation_grandtotal');
        });
    }
};
