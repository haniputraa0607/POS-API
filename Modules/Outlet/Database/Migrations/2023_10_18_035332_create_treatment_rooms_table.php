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
        Schema::create('treatment_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->string('name');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::table('treatment_outlets', function (Blueprint $table) {
            $table->after('outlet_id', function (Blueprint $table) {
                $table->foreignId('treatment_room_id')->nullable()->constrained('treatment_rooms');
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
        Schema::table('treatment_outlets', function (Blueprint $table) {
            $table->dropForeign('treatment_outlets_treatment_room_id_foreign');
            $table->dropColumn('treatment_room_id');
        });

        Schema::dropIfExists('treatment_rooms');
    }
};
