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
        if (!Schema::hasColumn('orders', 'parent_id')) {
            # code...
            Schema::table('orders', function (Blueprint $table) {
                $table->after('is_submited_doctor', function (Blueprint $table) {
                    $table->foreignId('parent_id')->nullable()->constrained('orders');
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
