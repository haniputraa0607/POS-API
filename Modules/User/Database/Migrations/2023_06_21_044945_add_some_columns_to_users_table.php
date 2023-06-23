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
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->default('$2y$10$4CmCne./LBVkIkI1RQghxOOZWuzk7bAW2kVtJ66uSUzmTM/wbyury')->after('phone');
			$table->integer('id_city')->unsigned()->nullable()->index('fk_users_cities')->after('birthdate');
            $table->string('address')->nullable()->after('id_city');
			$table->enum('gender', array('Male','Female'))->nullable()->after('address');
			$table->enum('level', array('Super Admin','Admin'))->default('Admin')->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password');
            $table->dropColumn('id_city');
            $table->dropColumn('address');
            $table->dropColumn('gender');
            $table->dropColumn('level');
        });
    }
};
