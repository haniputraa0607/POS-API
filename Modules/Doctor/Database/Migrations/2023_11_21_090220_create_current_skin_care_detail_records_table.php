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
        Schema::create('current_skin_care_detail_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_skin_care_id')->constrained('current_skin_care_records');
            $table->enum("type", ["product", "treatment"]);
            $table->string("product_name");
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_skin_care_detail_records');
    }
};
