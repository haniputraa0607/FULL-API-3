<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToProductPricesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('product_prices', function(Blueprint $table)
		{
			$table->foreign('id_outlet', 'fk_product_prices_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_product', 'fk_product_prices_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('product_prices', function(Blueprint $table)
		{
			$table->dropForeign('fk_product_prices_outlets');
			$table->dropForeign('fk_product_prices_products');
		});
	}

}
