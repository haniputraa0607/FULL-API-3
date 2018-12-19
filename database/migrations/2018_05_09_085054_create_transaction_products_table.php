<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionProductsTable extends Migration {

	public function up()
	{
		Schema::create('transaction_products', function(Blueprint $table)
		{
			$table->increments('id_transaction_product');
			$table->integer('id_transaction')->unsigned()->index('fk_transaction_products_transactions');
			$table->integer('id_product')->unsigned()->index('fk_transaction_products_products');
			$table->integer('transaction_product_qty');
			$table->integer('transaction_product_price');
			$table->integer('transaction_product_subtotal');
			$table->string('transaction_product_note')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transaction_products');
	}

}
