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
        Schema::table('outlets', function (Blueprint $table) {

            if (Schema::hasColumn('outlets', 'partner_equal_id')) {
                $table->dropForeign('outlets_partner_equal_id_foreign');
                $table->dropColumn('partner_equal_id');
            }

            if (Schema::hasColumn('outlets', 'images')) {
                $table->dropColumn('images');
            }


        });

        Schema::table('outlets', function (Blueprint $table) {

            $table->after('outlet_code', function (Blueprint $table) {
                $table->json('images')->nullable(true);
                $table->foreignId('partner_equal_id')->nullable()->constrained('partner_equals');
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
        Schema::table('outlets', function (Blueprint $table)
            $table->dropColumn('images');
            $table->dropColumn('partner_equal_id');
        });
    }
};
