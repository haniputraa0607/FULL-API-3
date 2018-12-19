<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTransactionPaymentOfflinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('transaction_payment_offlines', function(Blueprint $table)
		{
			$table->foreign('id_transaction', 'transaction_payment_offlines_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('transaction_payment_offlines', function(Blueprint $table)
		{
			$table->dropForeign('transaction_payment_offlines_transactions');
		});
	}

}
