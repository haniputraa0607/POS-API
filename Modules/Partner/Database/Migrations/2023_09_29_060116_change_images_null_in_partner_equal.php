<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // Hapus konstrain kunci asing yang merujuk ke kolom "city_code"
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->dropForeign('partner_equals_city_code_foreign');
        });

        // Ubah kolom "city_code" menjadi NULLABLE
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->json('images')->nullable()->change();
            $table->integer('city_code')->nullable()->change();
        });
    }

    public function down()
    {
        // Ubah kolom "city_code" menjadi NULLABLE
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->json('images')->nullable()->change();
            $table->integer('city_code')->nullable()->change();
        });

        // Tambahkan kembali konstrain kunci asing jika diperlukan
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->foreign('city_code')->references('code')->on('cities');
        });
    }

};
