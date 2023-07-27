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
        Schema::table('outlet_schedules', function (Blueprint $table) {
            $table->boolean('all_products')->default(true)->after('is_closed');
            $table->string('custom_products')->nullable()->after('is_closed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlet_schedules', function (Blueprint $table) {

        });
    }
};
