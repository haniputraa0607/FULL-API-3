<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToProductDiscountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('product_discounts', function(Blueprint $table)
		{
			$table->foreign('id_product', 'fk_product_discounts_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('product_discounts', function(Blueprint $table)
		{
			$table->dropForeign('fk_product_discounts_products');
		});
	}

}
