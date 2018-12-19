<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProductPhotosTable extends Migration {

	public function up()
	{
		Schema::create('product_photos', function(Blueprint $table)
		{
			$table->increments('id_product_photo');
			$table->integer('id_product')->unsigned()->index('fk_product_photos_products');
			$table->string('product_photo', 150);
			$table->smallInteger('product_photo_order')->default(0);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('product_photos');
	}

}
