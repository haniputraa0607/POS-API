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
        Schema::table('outlets', function (Blueprint $table) {
            if (env('DB_CONNECTION')=='pgsql') {
                # code...
                DB::statement('ALTER TABLE "outlets" ALTER COLUMN "status" SET DEFAULT \'Active\'');
            }else{
                $table->enum('status',["Active","Inactive"])->default('Active')->nullback(false)->change();

            }

            $table->after('updated_at', function (Blueprint $table) {
                $table->dateTime('deleted_at')->nullable();
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
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
