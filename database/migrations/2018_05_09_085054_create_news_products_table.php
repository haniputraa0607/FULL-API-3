<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNewsProductsTable extends Migration {

	public function up()
	{
		Schema::create('news_products', function(Blueprint $table)
		{
			$table->integer('id_product')->unsigned()->index('fk_news_products_products');
			$table->integer('id_news')->unsigned()->index('fk_news_products_news');
			$table->primary(['id_product','id_news']);
		});
	}

	public function down()
	{
		Schema::drop('news_products');
	}

}
