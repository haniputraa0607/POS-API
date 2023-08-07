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
        Schema::table('patient_grievances', function (Blueprint $table) {
            $table->dropColumn('id_transaction_consultation');
            $table->dropColumn('id_grievance');
            $table->after('id', function (Blueprint $table) {
                $table->foreignId('consultation_id')->constrained('consultations');
                $table->foreignId('grievance_id')->constrained('grievances');
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
        Schema::table('patient_grievances', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->unsignedInteger('id_transaction_consultation');
                $table->unsignedInteger('id_grievance');
            });
            $table->dropForeign('patient_grievances_consultation_id_foreign');
            $table->dropColumn('consultation_id');
            $table->dropForeign('patient_grievances_grievance_id_foreign');
            $table->dropColumn('grievance_id');
            $table->dropColumn('notes');
        });
    }
};
