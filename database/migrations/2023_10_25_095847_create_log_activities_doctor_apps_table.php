<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(env('SECOND_DB_DRIVER'))->dropIfExists('log_activities_doctor_apps');
        Schema::connection(env('SECOND_DB_DRIVER'))->create('log_activities_doctor_apps', function (Blueprint $table) {
            $table->id();
            $table->string('module')->nullable();
            $table->string('subject')->nullable();
            $table->string('url');
            $table->string('phone')->nullable();
            $table->longText('request')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('response_status', 7)->nullable();
            $table->longText('response')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->string('ip', 25)->nullable();
            $table->string('useragent', 200)->nullable();
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(env('SECOND_DB_DRIVER'))->dropIfExists('log_activities_doctor_apps');
    }
};
