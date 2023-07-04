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
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->string('diagnostic_name')->after('id');
            $table->string('description')->nullable()->after('diagnostic_name');
            $table->tinyInteger('is_active')->default(1)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->dropColumn('diagnostic_name');
            $table->dropColumn('description');
            $table->dropColumn('is_active');
        });
    }
};
