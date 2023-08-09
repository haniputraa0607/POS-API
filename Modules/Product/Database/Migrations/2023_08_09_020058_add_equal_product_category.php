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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->integer('equal_id')->nullable();
                $table->string('equal_name')->nullable();
                $table->string('equal_code')->nullable();
                $table->string('equal_parent_id')->nullable();
            });
            $table->after('description', function (Blueprint $table) {
                $table->string('product_category_photo')->nullable();
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
        Schema::table('', function (Blueprint $table) {
            $table->dropColumn('equal_id');
            $table->dropColumn('equal_name');
            $table->dropColumn('equal_code');
            $table->dropColumn('equal_parent_id');
            $table->dropColumn('product_category_photo');
        });
    }
};
