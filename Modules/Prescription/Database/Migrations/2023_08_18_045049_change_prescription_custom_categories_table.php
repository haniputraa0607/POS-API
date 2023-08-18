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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign('prescriptions_prescription_custom_category_id_foreign');
            $table->dropColumn('prescription_custom_category_id');
        });

        Schema::rename('prescription_custom_categories', 'prescription_categories');

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->string('unit')->nullable(true)->change();
            $table->integer('price')->nullable(true)->change();
            $table->after('prescription_name', function (Blueprint $table) {
                $table->foreignId('prescription_category_id')->constrained('prescription_categories');
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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign('prescriptions_prescription_category_id_foreign');
            $table->dropColumn('prescription_category_id');
        });

        Schema::rename('prescription_categories', 'prescription_custom_categories');

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('unit')->nullable(false)->change();
            $table->integer('price')->nullable(false)->change();
            $table->foreignId('prescription_custom_category_id')->nullable()->constrained('prescription_custom_categories');
            $table->after('prescription_name', function (Blueprint $table) {
                $table->string('type');
            });
        });
    }
};
