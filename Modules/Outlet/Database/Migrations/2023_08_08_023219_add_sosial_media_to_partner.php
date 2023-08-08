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
        Schema::table('partners', function (Blueprint $table) {
            $table->after('partner_phone', function (Blueprint $table) {
                $table->string('partner_location')->nullable();
                $table->string('partner_account_instagram')->nullable();
                $table->string('partner_account_shoope')->nullable();
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
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('partner_location');
            $table->dropColumn('partner_account_instagram');
            $table->dropColumn('partner_account_shoope');
        });
    }
};
