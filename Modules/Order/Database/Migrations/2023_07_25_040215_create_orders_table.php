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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('customers');
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('cashier_id')->constrained('users');
            $table->dateTime('order_date');
            $table->string('order_code');
            $table->string('notes')->nullable();
            $table->integer('order_subtotal')->default(0);
            $table->integer('order_gross')->default(0);
            $table->integer('order_discount')->default(0);
            $table->decimal('order_tax', $precision = 8, $scale = 2)->default(0);
            $table->integer('order_grandtotal')->default(0);
            $table->boolean('send_to_transaction')->default(false);
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('last_transaction');
            $table->after('count_transaction', function (Blueprint $table) {
                $table->foreignId('last_transaction_id')->nullable()->constrained('transactions');
                $table->foreignId('last_order_id')->nullable()->constrained('orders');
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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('last_transaction_id');
            $table->dropColumn('last_order_id');
            $table->after('email', function (Blueprint $table) {
                $table->integer('last_transaction')->nullable()->after('email');
            });
        });

        Schema::dropIfExists('orders');
    }
};
