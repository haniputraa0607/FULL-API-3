<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTransactionShipmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('transaction_shipments', function(Blueprint $table)
		{
			$table->foreign('depart_id_city', 'fk_transaction_shipments_shipments_depart')->references('id_city')->on('cities')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('destination_id_city', 'fk_transaction_shipments_shipments_destination')->references('id_city')->on('cities')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('id_transaction', 'fk_transaction_shipments_transactions')->references('id_transaction')->on('transactions')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('transaction_shipments', function(Blueprint $table)
		{
			$table->dropForeign('fk_transaction_shipments_shipments_depart');
			$table->dropForeign('fk_transaction_shipments_shipments_destination');
			$table->dropForeign('fk_transaction_shipments_transactions');
		});
	}

}
