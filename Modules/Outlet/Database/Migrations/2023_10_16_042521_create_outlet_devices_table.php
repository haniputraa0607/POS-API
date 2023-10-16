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
        Schema::create('outlet_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->date('date');
			$table->string('name');
			$table->string('device_id');
			$table->string('device_token')->nullable();
            $table->timestamps();
        });

        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->after('attendance_time', function (Blueprint $table) {
                $table->foreignId('outlet_device_id')->constrained('outlet_devices');
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
        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->dropForeign('employee_attendances_outlet_device_id_foreign');
            $table->dropColumn('outlet_device_id');
        });

        Schema::dropIfExists('outlet_devices');
    }
};
