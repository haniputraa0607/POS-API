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
        Schema::table('transaction_queues', function (Blueprint $table) {
            $table->string('consultation_queue')->nullable(true)->change();
            $table->string('product_queue')->nullable(true)->change();
            $table->string('treatment_queue')->nullable(true)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('id_outlet');
            $table->dropColumn('id_customer');
            $table->dropColumn('id_promo_campaign_code');
            $table->dropColumn('id_deal_voucher');
            $table->dropColumn('id_cashier');
            $table->foreignId('outlet_id')->constrained('outlets')->after('id');
            $table->foreignId('customer_id')->constrained('customers')->after('outlet_id');
            $table->foreignId('user_id')->constrained('users')->after('customer_id');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_queues', function (Blueprint $table) {
            $table->string('consultation_queue')->nullable(false)->change();
            $table->string('product_queue')->nullable(false)->change();
            $table->string('treatment_queue')->nullable(false)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('outlet_id');
            $table->dropColumn('customer_id');
            $table->dropColumn('user_id');
            $table->integer('id_outlet')->unsigned()->index('fk_transaction_outlet')->after('id');
			$table->integer('id_customer')->unsigned()->index('fk_transaction_customer')->after('id_outlet');
			$table->integer('id_promo_campaign_code')->nullable()->unsigned()->index('fk_transaction_promo')->after('id_customer');
			$table->integer('id_deal_voucher')->nullable()->unsigned()->index('fk_transaction_deal')->after('id_promo_campaign_code');
			$table->integer('id_cashier')->nullable()->unsigned()->index('fk_transaction_cashier')->after('id_deal_voucher');
        });
    }
};
