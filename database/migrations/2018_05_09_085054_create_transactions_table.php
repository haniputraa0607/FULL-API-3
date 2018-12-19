<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionsTable extends Migration {

	public function up()
	{
		Schema::create('transactions', function(Blueprint $table)
		{
			$table->increments('id_transaction');
			$table->integer('id_user')->unsigned()->nullable()->index('fk_transactions_users');
			$table->char('transaction_receipt_number', 18);
			$table->string('transaction_notes', 191)->nullable();
			$table->integer('transaction_subtotal');
			$table->integer('transaction_shipment');
			$table->integer('transaction_service');
			$table->integer('transaction_discount');
			$table->integer('transaction_tax');
			$table->integer('transaction_grandtotal');
			$table->integer('transaction_point_earned')->nullable();
			$table->integer('transaction_cashback_earned')->nullable();
			$table->enum('transaction_payment_status', array('Pending','Paid','Completed','Cancelled'));
			$table->dateTime('void_date')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('transactions');
	}

}
