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
            $table->char('district_code', 7);
            $table->foreign('district_code')
                ->references('code')
                ->on(config('indonesia.table_prefix') . 'districts')
                ->onUpdate('cascade')
                ->onDelete('restrict')->after('password');
            $table->string('address')->nullable()->after('district_code');
            $table->enum('gender', array('Male', 'Female'))->nullable()->after('address');
            $table->enum('level', array('Super Admin', 'Admin'))->default('Admin')->after('gender');
            $table->boolean('is_active')->default(true)->after('level');
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
            $table->dropColumn('district_code');
            $table->dropColumn('address');
            $table->dropColumn('gender');
            $table->dropColumn('level');
            $table->dropColumn('is_active');
        });
    }
};
