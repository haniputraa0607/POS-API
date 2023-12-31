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
        if (!Schema::hasColumn('partner_equals', 'type')) {
            Schema::table('partner_equals', function (Blueprint $table) {
                $table->after('phone', function (Blueprint $table) {
                    $table->enum('type', config('partner_equal_type'))->nullable();
                });
            });
        } else {
            Schema::table('partner_equals', function (Blueprint $table) {
                $table->after('phone', function (Blueprint $table) {
                    $table->enum('type', config('partner_equal_type'))->nullable()->change();
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
