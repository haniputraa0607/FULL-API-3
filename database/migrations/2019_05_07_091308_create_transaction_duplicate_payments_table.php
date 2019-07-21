<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionDuplicatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
	{
		Schema::create('transaction_duplicate_payments', function(Blueprint $table)
		{
			$table->increments('id_transaction_duplicate_payment');
			$table->integer('id_transaction_duplicate')->unsigned()->index('fk_transaction_duplicate_products_transaction_s');
			$table->string('payment_type');
			$table->string('payment_name');
			$table->integer('payment_amount');
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
        Schema::dropIfExists('transaction_duplicate_payments');
    }
}
