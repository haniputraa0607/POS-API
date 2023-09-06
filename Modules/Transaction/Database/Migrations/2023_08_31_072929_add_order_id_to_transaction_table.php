<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

            
            if (env('DB_CONNECTION')=='pgsql') {
                # code...
                // uncomment this to use in pgsql
                DB::raw('CREATE TYPE new_transaction_payment_type AS ENUM (\'Cash\', \'Xendit\', \'Midtrans\', \'Balance\', NULL);');
                DB::raw('ALTER TABLE transactions ADD COLUMN new_transaction_payment_type new_transaction_payment_type;');
                DB::raw('UPDATE transactions SET new_transaction_payment_type = transaction_payment_type;');
                DB::raw('ALTER TABLE transactions DROP COLUMN transaction_payment_type;');
                DB::raw('ALTER TABLE transactions RENAME COLUMN new_transaction_payment_type TO transaction_payment_type;');
            }else{
                // uncomment this to use in mysql
                $table->enum('transaction_payment_type',['Cash','Xendit','Midtrans','Balance'])->nullable(true)->change();

            }
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
            $table->enum('transaction_payment_type', ['Cash', 'Xendit', 'Midtrans', 'Balance'])->nullable(false)->default('Cash')->change();
        });
    }
};
