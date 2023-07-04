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
        Schema::table('grievances', function (Blueprint $table) {
            $table->string('grievance_name')->after('id');
            $table->string('description')->nullable()->after('grievance_name');
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
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropColumn('grievance_name');
            $table->dropColumn('description');
            $table->dropColumn('is_active');
        });
    }
};
