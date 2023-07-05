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
        Schema::table('patient_diagnostics', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_consultation')->after('id');
            $table->unsignedInteger('id_diagnostic')->after('id_transaction_consultation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patient_diagnostics', function (Blueprint $table) {
            $table->dropColumn('id_transaction_consultation');
            $table->dropColumn('id_diagnostic');
        });
    }
};
