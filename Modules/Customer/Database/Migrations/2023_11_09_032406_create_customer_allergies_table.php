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
        Schema::create('customer_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allergy_id')->constrained('allergies');
            $table->foreignId('customer_id')->constrained('customers');
            $table->text('notes');
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->after('last_order_id', function(Blueprint $table){
                $table->boolean('is_allergy')->default(false);
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
        Schema::dropIfExists('customer_allergies');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_allergy');
        });
    }
};
