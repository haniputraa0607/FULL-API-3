<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProductDiscountsTable extends Migration {

	public function up()
	{
		Schema::create('product_discounts', function(Blueprint $table)
		{
			$table->increments('id_product_discount');
			$table->integer('id_product')->unsigned()->index('fk_product_discounts_products');
			$table->integer('discount_percentage')->nullable();
			$table->integer('discount_nominal')->nullable();
			$table->date('discount_start');
			$table->date('discount_end');
			$table->time('discount_time_start');
			$table->time('discount_time_end');
			$table->string('discount_days', 191)->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('product_discounts');
	}

}
