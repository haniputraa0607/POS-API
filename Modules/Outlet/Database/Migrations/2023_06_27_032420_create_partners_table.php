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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code');
            $table->string('partner_name');
            $table->string('partner_email')->nullback();
            $table->string('partner_phone')->nullback();
            $table->string('partner_address')->nullback();
            $table->timestamps();
        });

        Schema::table('outlets', function (Blueprint $table) {
			$table->integer('id_partner')->unsigned()->index('fk_outlet_partner')->after('id');
            $table->string('outlet_code')->after('id_partner');
			$table->integer('id_city')->unsigned()->nullable()->index('fk_outlet_cities')->after('outlet_code');
            $table->string('outlet_phone')->after('name');
            $table->string('outlet_email')->after('outlet_phone')->nullback();
            $table->string('outlet_latitude')->after('activities')->nullback();
            $table->string('outlet_longitude')->after('outlet_latitude')->nullback();
            $table->enum('status',["Active","Inactive"])->after('outlet_longitude')->nullback('Active');
            $table->integer('is_tax')->after('status')->nullback(1);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners');

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('id_partner');
            $table->dropColumn('outlet_code');
            $table->dropColumn('id_city');
            $table->dropColumn('outlet_phone');
            $table->dropColumn('outlet_email');
            $table->dropColumn('outlet_latitude');
            $table->dropColumn('outlet_longitude');
            $table->dropColumn('status');
            $table->dropColumn('is_tax');
        });
    }
};
