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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_categoriy_id')->nullable(true)->constrained('product_categories');
            $table->string('product_code');
            $table->string('product_name');
            $table->enum('type', config('product_type'))->default('Product');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('need_recipe_status')->default(true);
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
        Schema::dropIfExists('products');
    }
};
