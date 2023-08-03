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
            $table->dropColumn('id_transaction_consultation');
            $table->dropColumn('id_diagnostic');
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('consultation_id')->constrained('consultations');
                $table->foreignId('diagnostic_id')->constrained('diagnostics');
                $table->text('notes')->nullable();
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
        Schema::table('patient_diagnostics', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->unsignedInteger('id_transaction_consultation');
                $table->unsignedInteger('id_diagnostic');
            });
            $table->dropForeign('patient_diagnostics_consultation_id_foreign');
            $table->dropColumn('consultation_id');
            $table->dropForeign('patient_diagnostics_diagnostic_id_foreign');
            $table->dropColumn('diagnostic_id');
            $table->dropColumn('notes');
        });
    }
};
