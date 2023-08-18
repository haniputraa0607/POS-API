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
        Schema::create('prescription_custom_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->timestamps();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->after('patient_id', function (Blueprint $table) {
                $table->foreignId('prescription_custom_category_id')->nullable()->constrained('prescription_custom_categories');
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
        Schema::dropIfExists('prescription_custom_categories');
    }
};
