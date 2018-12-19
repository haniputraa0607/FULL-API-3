<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionPaymentOfflinesTable extends Migration {

	public function up()
	{
		Schema::create('transaction_payment_offlines', function(Blueprint $table)
		{
			$table->increments('id_transaction_payment_offline');
			$table->integer('id_transaction')->unsigned()->nullable()->index('transaction_payment_offlines_transactions');
			$table->string('payment_type', 200);
			$table->string('payment_bank', 200)->nullable();
			$table->integer('payment_amount');
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transaction_payment_offlines');
	}

}
