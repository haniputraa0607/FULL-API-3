<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToNewsProductsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('news_products', function(Blueprint $table)
		{
			$table->foreign('id_news', 'fk_news_products_news')->references('id_news')->on('news')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_product', 'fk_news_products_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('news_products', function(Blueprint $table)
		{
			$table->dropForeign('fk_news_products_news');
			$table->dropForeign('fk_news_products_products');
		});
	}

}
