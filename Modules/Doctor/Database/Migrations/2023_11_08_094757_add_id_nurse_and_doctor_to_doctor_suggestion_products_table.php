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
        Schema::table('doctor_suggestion_products', function (Blueprint $table) {
            $table->after('qty', function (Blueprint $table) {
                $table->foreignId('doctor_id')->nullable()->constrained('users');
                $table->foreignId('nurse_id')->nullable()->constrained('nurses');
                $table->foreignId('beautician_id')->nullable()->constrained('beauticians');
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
        Schema::table('doctor_suggestion_products', function (Blueprint $table) {
            $table->dropForeign('doctor_suggestion_products_doctor_id_foreign');
            $table->dropColumn('doctor_id');
            $table->dropForeign('doctor_suggestion_products_nurse_id_foreign');
            $table->dropColumn('nurse_id');
            $table->dropForeign('doctor_suggestion_products_beautician_id_foreign');
            $table->dropColumn('beautician_id');
        });
    }
};
