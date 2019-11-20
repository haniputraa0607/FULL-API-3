<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoriteModifiersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('favorite_modifiers', function (Blueprint $table) {
			$table->unsignedInteger('id_favorite');
			$table->unsignedInteger('id_product_modifier');

			$table->foreign('id_favorite','fk_favorite_modifiers_id_favorite')->references('id_favorite')->on('favorites')->onDelete('CASCADE');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('favorite_modifiers');
	}
}
