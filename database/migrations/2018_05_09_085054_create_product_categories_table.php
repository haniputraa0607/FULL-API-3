<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProductCategoriesTable extends Migration {

	public function up()
	{
		Schema::create('product_categories', function(Blueprint $table)
		{
			$table->increments('id_product_category');
			$table->integer('id_parent_category')->unsigned()->nullable()->index('fk_product_categories_product_categories');
			$table->boolean('product_category_order')->nullable();
			$table->string('product_category_name', 200);
			$table->text('product_category_description', 65535)->nullable();
			$table->string('product_category_photo', 200)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('product_categories');
	}

}
