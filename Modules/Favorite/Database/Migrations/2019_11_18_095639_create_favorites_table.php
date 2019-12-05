<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoritesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('favorites', function (Blueprint $table) {
			$table->increments('id_favorite');
			$table->unsignedInteger('id_product');
			$table->unsignedInteger('id_user');
			$table->unsignedInteger('id_outlet');
			$table->unsignedInteger('product_qty');
			$table->text('notes');
			$table->timestamps();

			$table->foreign('id_product','fk_favorites_id_product')->references('id_product')->on('products')->onDelete('CASCADE');
			$table->foreign('id_user','fk_favorites_id_user')->references('id')->on('users')->onDelete('CASCADE');
			$table->foreign('id_outlet','fk_favorites_id_outlet')->references('id_outlet')->on('outlets')->onDelete('CASCADE');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('favorites');
	}
}
