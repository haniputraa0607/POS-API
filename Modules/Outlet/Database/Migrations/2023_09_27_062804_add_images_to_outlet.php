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
        if (!Schema::hasColumns('outlets', ['images', 'partner_equal_id'])) {
            Schema::table('outlets', function (Blueprint $table) {
                $table->after('outlet_code', function (Blueprint $table) {
                    $table->json('images')->nullable(true);
                    $table->foreignId('partner_equal_id')->default(0)->constrained('partner_equals');
                });
            });
        } else {
            Schema::table('outlets', function (Blueprint $table) {
                $table->json('images')->nullable(true)->change();
                // $table->foreignId('partner_equal_id')->default(0)->constrained('partner_equals')->change();
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
        Schema::table('outlets', function (Blueprint $table){
            $table->dropColumn('images');
            $table->dropColumn('partner_equal_id');
        });
    }
};
