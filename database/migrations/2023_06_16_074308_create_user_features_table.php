<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_features', function(Blueprint $table)
		{
			$table->integer('id_user')->unsigned()->index('fk_user_features_users');
			$table->integer('id_feature')->unsigned()->index('fk_user_features_features');
			$table->primary(['id_user','id_feature']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_features');
    }
};
