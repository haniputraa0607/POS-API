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
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->after('phone', function (Blueprint $table) {
                $table->json('images');
                $table->char('city_code', 4);
                $table->foreign('city_code')
                        ->references('code')
                        ->on(config('indonesia.table_prefix').'cities')
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
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
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->dropColumn('images');
            $table->dropColumn('city_code');
        });
    }
};
