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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
			$table->integer('id_outlet')->unsigned()->index('fk_transaction_outlet');
			$table->integer('id_customer')->unsigned()->index('fk_transaction_customer');
			$table->integer('id_promo_campaign_code')->nullable()->unsigned()->index('fk_transaction_promo');
			$table->integer('id_deal_voucher')->nullable()->unsigned()->index('fk_transaction_deal');
			$table->integer('id_cashier')->nullable()->unsigned()->index('fk_transaction_cashier');
            $table->dateTime('transaction_date');
            $table->dateTime('completed_at')->nullable();
            $table->string('transaction_receipt_number');
            $table->string('transaction_notes')->nullable();
            $table->integer('transaction_subtotal');
            $table->integer('transaction_gross');
            $table->integer('transaction_discount');
            $table->decimal('transaction_tax', $precision = 8, $scale = 2);
            $table->integer('transaction_grandtotal');
            $table->enum('transaction_payment_type',['Cash','Xendit','Midtrans','Balance'])->default('Cash');
            $table->enum('transaction_payment_status',['Pending','Completed','Cancelled'])->default('Pending');
            $table->dateTime('void_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
