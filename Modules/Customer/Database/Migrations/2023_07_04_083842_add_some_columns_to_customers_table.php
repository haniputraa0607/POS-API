<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Customer\Entities\Customer;

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
            $table->enum('gender',Customer::GENDER)->after('name');
            $table->date('birth_date')->after('gender');
            $table->string('phone')->unique()->after('birth_date');
            $table->string('email')->unique()->after('phone');
            $table->boolean('is_active')->default(true);
            $table->integer('last_transaction')->default(0)->after('email');
            $table->integer('count_transaction')->default(0)->after('last_transaction');
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
