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
        if (!Schema::hasColumns('order_prescriptions', ['queue', 'queue_code'])) {
            # code...
            Schema::table('order_prescriptions', function (Blueprint $table) {
                $table->after('order_prescription_grandtotal', function (Blueprint $table) {
                    $table->integer('queue')->nullable();
                    $table->string('queue_code')->nullable();
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_prescriptions', function (Blueprint $table) {
            $table->dropColumn('queue');
            $table->dropColumn('queue_code');
        });
    }
};
