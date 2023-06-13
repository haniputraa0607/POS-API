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
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->char('district_code', 7);
            $table->foreign('district_code')
                    ->references('code')
                    ->on(config('indonesia.table_prefix').'districts')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            $table->string('postal_code')->nullable();
            $table->jsonb('coordinates')->nullable();
            $table->json('activities');
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
        Schema::dropIfExists('outlets');
    }
};
