<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProductsTable extends Migration {

	public function up()
	{
		Schema::create('products', function(Blueprint $table)
		{
			$table->increments('id_product');
			$table->integer('id_product_category')->unsigned()->nullable()->index('fk_products_product_categories');
			$table->string('product_code', 45)->nullable();
			$table->string('product_name', 200);
			$table->string('product_name_pos', 200);
			$table->text('product_description', 16777215)->nullable();
			$table->string('product_video', 200)->nullable();
			$table->integer('product_weight')->default(0);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('products');
	}

}
