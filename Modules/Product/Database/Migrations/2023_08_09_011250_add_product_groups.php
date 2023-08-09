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
        Schema::table('products', function (Blueprint $table) {
            $table->after('equal_name', function (Blueprint $table) {
                $table->json('equal_id_category')->nullable();
            });
            $table->after('image', function (Blueprint $table) {
                $table->json('product_groups')->nullable();
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
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('equal_id_category');
            $table->dropColumn('product_groups');
        });
    }
};
