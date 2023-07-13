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
        Schema::table('customers', function (Blueprint $table) {
            $table->after('count_transaction', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
            $table->integer('last_transaction')->nullable(true)->change();
            $table->integer('count_transaction')->default(0)->change();
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
            $table->integer('last_transaction')->nullable(false)->change();
            $table->integer('count_transaction')->nullable(false)->change();
            $table->dropColumn('is_active');
        });
    }
};
