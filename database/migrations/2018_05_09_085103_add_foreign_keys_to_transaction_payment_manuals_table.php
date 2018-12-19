<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTransactionPaymentManualsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('transaction_payment_manuals', function(Blueprint $table)
		{
			$table->foreign('id_transaction', 'fk_transaction_payment_manuals_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_manual_payment_method', 'fk_transaction_payments_manual_payments')->references('id_manual_payment_method')->on('manual_payment_methods')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_user_confirming', 'fk_transaction_payments_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('transaction_payment_manuals', function(Blueprint $table)
		{
			$table->dropForeign('fk_transaction_payment_manuals_transactions');
			$table->dropForeign('fk_transaction_payments_manual_payments');
			$table->dropForeign('fk_transaction_payments_users');
		});
	}

}
