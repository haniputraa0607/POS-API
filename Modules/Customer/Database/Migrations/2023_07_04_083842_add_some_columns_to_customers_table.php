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
            $table->string('name')->after('id');
            $table->enum('gender',['Male','Female'])->after('name');
            $table->date('birth_date')->after('gender');
            $table->string('phone')->after('birth_date');
            $table->string('email')->after('phone');
            $table->integer('last_transaction')->after('email');
            $table->integer('count_transaction')->after('last_transaction');
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
            $table->dropColumn('name');
            $table->dropColumn('gender');
            $table->dropColumn('birth_date');
            $table->dropColumn('phone');
            $table->dropColumn('email');
            $table->dropColumn('last_transaction');
            $table->dropColumn('count_transaction');
        });
    }
};
