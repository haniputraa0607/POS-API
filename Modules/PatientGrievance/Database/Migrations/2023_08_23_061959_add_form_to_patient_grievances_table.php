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
            $table->after('grievance_id', function (Blueprint $table) {
                $table->boolean('from_pos')->default(0);
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
            $table->dropColumn('from_pos');
        });
    }
};
