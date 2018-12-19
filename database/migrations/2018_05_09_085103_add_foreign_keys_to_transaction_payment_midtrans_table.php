<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTransactionPaymentMidtransTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('transaction_payment_midtrans', function(Blueprint $table)
		{
			$table->foreign('id_transaction', 'fk_transaction_payments_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('transaction_payment_midtrans', function(Blueprint $table)
		{
			$table->dropForeign('fk_transaction_payments_transactions');
		});
	}

}
