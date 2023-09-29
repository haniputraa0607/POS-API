<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('equal_id')->nullable()->change();
            $table->boolean('is_active')->default(true);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('equal_id')->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('equal_id')->nullable()->change();
        });
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->string('equal_id')->nullable()->change();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (env('DB_CONNECTION') == 'pgsql') {
            DB::statement('ALTER TABLE product_categories ALTER COLUMN equal_id TYPE integer USING equal_id::integer');
            DB::statement('ALTER TABLE products ALTER COLUMN equal_id TYPE integer USING equal_id::integer');
            DB::statement('ALTER TABLE users ALTER COLUMN equal_id TYPE integer USING equal_id::integer');
            DB::statement('ALTER TABLE partner_equals ALTER COLUMN equal_id TYPE integer USING equal_id::integer');
        } else {
            DB::statement('ALTER TABLE product_categories MODIFY COLUMN equal_id INT');
            DB::statement('ALTER TABLE products MODIFY COLUMN equal_id INT');
            DB::statement('ALTER TABLE users MODIFY COLUMN equal_id INT');
            DB::statement('ALTER TABLE partner_equals MODIFY COLUMN equal_id INT');
        }

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('partner_equals', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
    
};
