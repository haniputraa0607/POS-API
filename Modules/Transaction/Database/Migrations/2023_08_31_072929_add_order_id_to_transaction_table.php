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
        Schema::table('transactions', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('order_id')->constrained('orders');
            });
            $table->enum('transaction_payment_type',['Cash','Xendit','Midtrans','Balance'])->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign('transactions_order_id_foreign');
            $table->dropColumn('order_id');
            $table->enum('transaction_payment_type',['Cash','Xendit','Midtrans','Balance'])->nullable(false)->default('Cash')->change();
        });
    }
};
