<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            //commant for mysql
            $table->enum('status',["Active","Inactive"])->nullback(false)->change(); 
            //commant for pgsql
            // DB::statement('ALTER TABLE "outlets" ALTER COLUMN "status" SET DEFAULT \'Active\'');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            //commant for pgsql
            // DB::statement('ALTER TABLE "outlets" ALTER COLUMN "status" DROP DEFAULT');
        });
    }
};
