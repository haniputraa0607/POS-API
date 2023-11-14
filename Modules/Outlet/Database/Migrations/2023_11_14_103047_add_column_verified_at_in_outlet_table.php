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
        Schema::table('outlets', function (Blueprint $table) {
            $table->after('partner_equal_id', function (Blueprint $table) {
                $table->dateTime('verified_at')->nullable();
            });
            $table->after('id', function (Blueprint $table) {
                $table->string('equal_id')->unique()->nullable();
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
        Schema::table('outlet_id', function (Blueprint $table) {
            $table->dropColumn('verified_at');
            $table->dropColumn('equal_id');
        });
    }
};
