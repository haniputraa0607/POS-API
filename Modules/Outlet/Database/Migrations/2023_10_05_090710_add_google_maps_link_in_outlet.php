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
        if (!Schema::hasColumn('outlets', 'google_maps_link')) {
            Schema::table('outlets', function (Blueprint $table) {
                $table->after('coordinates', function (Blueprint $table) {
                    $table->string('google_maps_link')->nullable(true);
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
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('google_maps_link');
        });
    }
};
