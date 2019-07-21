<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionDuplicateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
	{
		Schema::create('transaction_duplicate_products', function(Blueprint $table)
		{
			$table->increments('id_transaction_duplicate_product');
			$table->integer('id_transaction_duplicate')->unsigned()->index('fk_transaction_duplicate_products_transaction_s');
			$table->integer('id_product')->unsigned()->index('fk_transaction_duplicate_products_products');
			$table->string('transaction_product_code');
            $table->string('transaction_product_name');	
            $table->integer('transaction_product_qty');
			$table->decimal('transaction_product_price', 10, 2);
			$table->decimal('transaction_product_subtotal', 10, 2);
			$table->string('transaction_product_note')->nullable();
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_duplicate_products');
    }
}
