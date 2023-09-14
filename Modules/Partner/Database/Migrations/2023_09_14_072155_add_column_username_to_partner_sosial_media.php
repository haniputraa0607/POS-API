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
        Schema::table('partner_sosial_medias', function (Blueprint $table) {
            $table->after('partner_store_id', function (Blueprint $table) {
                $table->string('username')->nullable();
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
        Schema::table('partner_sosial_medias', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
