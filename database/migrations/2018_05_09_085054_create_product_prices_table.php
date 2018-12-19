<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProductPricesTable extends Migration {

	public function up()
	{
		Schema::create('product_prices', function(Blueprint $table)
		{
			$table->increments('id_product_price');
			$table->integer('id_product')->unsigned()->nullable()->index('fk_product_prices_products');
			$table->integer('id_outlet')->unsigned()->nullable()->index('fk_product_prices_outlets');
			$table->integer('product_price')->unsigned()->nullable();
			$table->enum('product_visibility', array('Hidden','Visible'))->default('Hidden');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('product_prices');
	}

}
